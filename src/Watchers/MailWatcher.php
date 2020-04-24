<?php

namespace Isotopes\Profiler\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Mail\Events\MessageSent;
use Isotopes\Profiler\Entry\IncomingEntry;
use Isotopes\Profiler\Profiler;
use Swift_Message;

class MailWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  Application  $app
     * @return void
     */
    public function register($app): void
    {
        $app['events']->listen(MessageSent::class, [$this, 'recordMail']);
    }

    /**
     * Record a mail message sent.
     *
     * @param MessageSent $event
     * @return void
     */
    public function recordMail(MessageSent $event): void
    {
        if (! Profiler::isRecording()) {
            return;
        }

        Profiler::recordMail(IncomingEntry::make([
            'mailable' => $this->getMailable($event),
            'queued'   => $this->getQueuedStatus($event),
            'from'     => $event->message->getFrom(),
            'replyTo'  => $event->message->getReplyTo(),
            'to'       => $event->message->getTo(),
            'cc'       => $event->message->getCc(),
            'bcc'      => $event->message->getBcc(),
            'subject'  => $event->message->getSubject(),
            'html'     => $event->message->getBody(),
            'raw'      => $event->message->toString(),
        ])->tags($this->tags($event->message, $event->data)));
    }

    /**
     * Get the name of the mailable.
     *
     * @param MessageSent $event
     * @return string
     */
    protected function getMailable($event): string
    {
        if (isset($event->data['__laravel_notification'])) {
            return $event->data['__laravel_notification'];
        }

        return $event->data['__profiler_mailable'] ?? '';
    }

    /**
     * Determine whether the mailable was queued.
     *
     * @param MessageSent $event
     * @return bool
     */
    protected function getQueuedStatus($event): bool
    {
        if (isset($event->data['__laravel_notification_queued'])) {
            return $event->data['__laravel_notification_queued'];
        }

        return $event->data['__profiler_queued'] ?? false;
    }

    /**
     * Extract the tags from the message.
     *
     * @param  Swift_Message  $message
     * @param  array  $data
     * @return array
     */
    private function tags($message, $data): array
    {
        return array_merge(
            array_keys($message->getTo() ?: []),
            array_keys($message->getCc() ?: []),
            array_keys($message->getBcc() ?: []),
            $data['__profiler'] ?? []
        );
    }
}
