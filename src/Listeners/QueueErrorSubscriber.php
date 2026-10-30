<?php

namespace Sadpcd\ErrorAlerts\Listeners;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\JobFailed;
use Sadpcd\ErrorAlerts\Services\ErrorAlertManager;
use Throwable;

class QueueErrorSubscriber
{
    protected ErrorAlertManager $manager;

    public function __construct(ErrorAlertManager $manager)
    {
        $this->manager = $manager;
    }

    public function handleJobFailed(JobFailed $event): void
    {
        if (!config('error-alerts.capture.queue', true)) {
            return;
        }

        try {
            $jobName = $event->job->resolveName();
            $connection = $event->connectionName;
            $queue = $event->job->getQueue();

            $extra = [
                'job_name'        => $jobName,
                'queue_connection'=> $connection,
                'queue_name'      => $queue,
                'attempts'        => $event->job->attempts(),
            ];

            $this->manager->handle($event->exception, 'queue', $extra);
        } catch (Throwable) {
            // fail-safe
        }
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(JobFailed::class, [self::class, 'handleJobFailed']);
    }
}
