<?php

namespace NotificationChannels\Infobip;

use infobip\api\client\SendMultipleTextualSmsAdvanced;
use infobip\api\client\SendSingleTextualSms;
use infobip\api\configuration\BasicAuthConfiguration;
use infobip\api\model\Destination;
use infobip\api\model\sms\mt\send\Message;
use infobip\api\model\sms\mt\send\textual\SMSAdvancedTextualRequest;
use infobip\api\model\sms\mt\send\textual\SMSTextualRequest;
use NotificationChannels\Infobip\Exceptions\CouldNotSendNotification;

class Infobip
{
    /**
     * @var InfobipConfig
     */
    public $config;

    /**
     * @var InfobipMessage
     */
    public $message;

    /**
     * Infobip constructor.
     *
     * @param InfobipMessage $message
     * @param InfobipConfig $config
     */
    public function __construct(InfobipMessage $message, InfobipConfig $config)
    {
        $this->config = $config;
        $this->message = $message;
    }

    /**
     * Send sms message to recipient.
     *
     * @param InfobipMessage $message
     * @param $recipient
     * @return \infobip\api\model\sms\mt\send\SMSResponse
     * @throws CouldNotSendNotification
     */
    public function sendMessage(InfobipMessage $message, $recipient)
    {
        if ($message instanceof InfobipMessage) {
            $message->from($this->config->getFrom());

            return $this->sendSms($message, $recipient);
        }

        if ($message instanceof InfobipSmsAdvancedMessage) {
            $message->from($this->config->getFrom());
            $message->notifyUrl($this->config->getNotifyUrl());

            return $this->sendSmsAdvanced($message, $recipient);
        }

        throw CouldNotSendNotification::invalidMessageObject($message);
    }

    /**
     * Send sms message to recipient using Infobip SMSTextualRequest.
     *
     * @param InfobipMessage $message
     * @param $recipient
     * @return \infobip\api\model\sms\mt\send\SMSResponse
     */
    public function sendSms(InfobipMessage $message, $recipient)
    {
        $client = new SendSingleTextualSms(new BasicAuthConfiguration($this->config->config['username'], $this->config->config['password']));

        $request = new SMSTextualRequest();
        $request->setFrom($this->getFrom($message));
        $request->setTo($recipient);
        $request->setText($message->content);

        return $client->execute($request);
    }

    /**
     * Send sms advanced to recipient using SMSAdvancedTextualRequest.
     *
     * @param InfobipSmsAdvancedMessage $message
     * @param $recipient
     * @return \infobip\api\model\sms\mt\send\SMSResponse
     */
    public function sendSmsAdvanced(InfobipSmsAdvancedMessage $message, $recipient)
    {
        $client = new SendMultipleTextualSmsAdvanced(new BasicAuthConfiguration($this->config->config['username'], $this->config->config['password']));

        $destination = new Destination();

        $destination->setTo($recipient);

        $requestMessage = new Message();
        $requestMessage->setFrom($this->getFrom($message));
        $requestMessage->setDestinations([$destination]);
        $requestMessage->setText($message->content);
        $requestMessage->setNotifyUrl($this->getNotifyUrl($message));

        $request = new SMSAdvancedTextualRequest();
        $request->setMessages([$requestMessage]);

        return $client->execute($request);
    }

    /**
     * Get message from phone number from message or config.
     *
     * @param InfobipMessage $message
     * @return mixed
     * @throws CouldNotSendNotification
     */
    public function getFrom(InfobipMessage $message)
    {
        if (! $from = $message->from ?: $this->config->config['from']) {
            throw CouldNotSendNotification::missingFrom();
        }

        return $message->from ?: $this->config->config['from'];
    }

    /**
     * Get sms notify url.
     *
     * @param InfobipSmsAdvancedMessage $message
     * @return mixed
     * @throws CouldNotSendNotification
     */
    public function getNotifyUrl(InfobipSmsAdvancedMessage $message)
    {
        if (! $notifyUrl = $message->notifyUrl ?: $this->config->config['notify_url']) {
            throw CouldNotSendNotification::missingNotifyUrl();
        }

        return $message->notify_url ?: $this->config->config['notify_url'];
    }
}
