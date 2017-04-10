<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Worker;

use Netric\EntityQuery;
use Netric\WorkerMan\Job;
use Netric\WorkerMan\AbstractWorker;

/**
 * This worker is used to synchronize changes with a mailbox
 */
class EmailMailboxSyncWorker extends AbstractWorker
{
    /**
     * Synchronize changes with a remote server
     *
     * @param Job $job
     * @return mixed The reversed string
     */
    public function work(Job $job)
    {
        $workload = $job->getWorkload();
        $application = $this->getApplication();
        $log = $application->getLog();

        $log->info("EmailMailboxSyncWorker->work: [STARTED]");

        // Make sure we have the required data
        if (
            !isset($workload['account_id']) ||
            !isset($workload['user_id']) ||
            !isset($workload['mailbox_id'])
        ) {
            $log->info(
                "EmailMailboxSyncWorker->work: fields required account_id, user_id, mailbox_id " .
                var_export($workload, true)
            );
            return false;
        }

        $log->info("EmailMailboxSyncWorker->work: for {$workload['account_id']}, {$workload['user_id']}");

        // Get the account and user we are working with
        $application = $this->getApplication();
        $account = $application->getAccount($workload['account_id']);
        $user = $account->getServiceManager()->get("EntityLoader")->get("user", $workload['user_id']);
        // Fail if not a valid user
        if (!$user) {
            $log->info(
                "EmailMailboxSyncWorker->work: user_id  {$workload['user_id']} is not valid"
            );
            return false;
        }
        $account->setCurrentUser($user);

        // Get the entity index and the email receiver
        $entityIndex = $account->getServiceManager()->get("EntityQuery_Index");
        $mailReceiver = $account->getServiceManager()->get("Netric/Mail/ReceiverService");
        $entityLoader = $account->getServiceManager()->get("EntityLoader");

        // Get email accounts
        $query = new EntityQuery("email_account");
        $query->where("owner_id")->equals($workload['user_id']);
        $query->andWhere("type")->doesNotEqual("");
        $results = $entityIndex->executeQuery($query);
        $num = $results->getTotalNum();
        for ($i = 0; $i < $num; $i++) {
            $emailAccount = $results->getEntity($i);

            /*
             * If this account is in the process of being synchronized, and that process
             * started less than an hour ago, then we will just skipe the account.
             */
            if ($emailAccount->getValue("f_synchronizing") &&
                (
                    empty($emailAccount->getValue("ts_last_full_sync")) ||
                    $emailAccount->getValue("ts_last_full_sync") > (time() - 60*60)
                )
            ) {
                $log->info(
                    "EmailMailboxSyncWorker->work: syncMailbox: " .
                    $emailAccount->getValue("address") .
                    " is already being synchronized, so skipping for now."
                );
                continue;
            }

            // Update flag to let any other processes know that we are synchronizing
            $emailAccount->setValue("f_synchronizing", true);
            $entityLoader->save($emailAccount);

            $log->info("EmailMailboxSyncWorker->work: syncMailbox: " . $emailAccount->getValue("address"));
            $mailReceiver->syncMailbox($workload['mailbox_id'], $emailAccount);
            $job->sendStatus($i+1, $num);

            // Update last full sync of account
            $emailAccount->setValue("ts_last_full_sync", time());
            $emailAccount->setValue("f_synchronizing", false);
            $entityLoader->save($emailAccount);
        }

        $log->info("EmailMailboxSyncWorker->work: [DONE]");

        return true;
    }
}
