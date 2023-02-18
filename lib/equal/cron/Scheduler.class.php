<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace equal\cron;

use equal\organic\Service;
use equal\services\Container;


class Scheduler extends Service {


    /**
     * This method cannot be called directly (should be invoked through Singleton::getInstance)
     */
    protected function __construct(Container $container) {
    }

    public static function constants() {
        return [];
    }

    /**
     * Runs a batch of scheduled tasks.
     *
     * At each call we check all active tasks and execute the ones having the `moment` field (timestamp) overdue.
     * For recurring tasks we update the moment field to the next time, according to repeat axis and repeat step.
     * Non-recurring tasks are deleted once they've been run.
     *
     * #memo - Scheduler always operates as root user.
     */
    public function run() {
        $orm = $this->container->get('orm');

        $tasks_ids = $orm->search('core\Task', ['is_active', '=', true], ['moment' => 'asc'], 0, 10);

        if($tasks_ids > 0) {
            $now = time();
            $tasks = $orm->read('core\Task', $tasks_ids, ['id', 'moment', 'is_recurring', 'repeat_axis', 'repeat_step', 'controller', 'params']);
            foreach($tasks as $tid => $task) {
                if($task['moment'] <= $now) {
                    // update task, if recurring
                    // #memo - we must start by doing this because some controller might run for a long time (longer than the next `run()` call)
                    if($task['is_recurring']) {
                        $moment = $task['moment'];
                        while($moment < $now) {
                            $moment = strtotime("+{$task['repeat_step']} {$task['repeat_axis']}", $moment);
                        }
                        $orm->update('core\Task', $tid, ['moment' => $moment]);
                    }
                    else {
                        $orm->remove('core\Task', $tid, true);
                    }
                    // run
                    try {
                        $body = json_decode($task['params'], true);
                        \eQual::run('do', $task['controller'], $body, true);
                    }
                    catch(\Exception $e) {
                        // error occurred during execution
                        trigger_error("PHP::Error while running scheduled job [{$task['id']}]: ".$e->getMessage(), QN_REPORT_ERROR);
                    }
                }
            }
        }
    }

    /**
     * Schedules a new task.
     * #memo - Scheduler always operates as root user.
     *
     * @param   string    $name         Name of the task to schedule, to ease task identification.
     * @param   integer   $moment       Timestamp of the moment of the first execution.
     * @param   string    $controller   Controller to invoker, with package notation.
     * @param   string    $params       JSON string holding the payload to relay to the controller.
     * @param   boolean   $recurring    Flag to mark the task as recurring.
     */
    public function schedule($name, $moment, $controller, $params, $recurring=false, $repeat_axis='day', $repeat_step='1') {
        $orm = $this->container->get('orm');
        trigger_error("PHP::Scheduling job", QN_REPORT_INFO);

        $orm->create('core\Task', [
            'name'          => $name,
            'moment'        => $moment,
            'controller'    => $controller,
            'params'        => json_encode($params),
            'is_recurring'  => $recurring
        ]);
    }

    /**
     * Cancels a scheduled task (by deleting it).
     * #memo - Scheduler always operates as root user.
     *
     * @param   string    $name         Name of the task to cancel.
     */
    public function cancel($name) {
        $orm = $this->container->get('orm');
        $tasks_ids = $orm->search('core\Task', ['name', '=', $name]);
        if($tasks_ids > 0 && count($tasks_ids)) {
            $orm->remove('core\Task', $tasks_ids, true);
        }
    }

}
