<?php

namespace App\Services\Camt\Conversion;

use App\Services\Camt\Transaction;
use App\Services\Shared\Configuration\Configuration;

class TransactionConverter
{
    private Configuration $configuration;

    /**
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param array $transactions
     *
     * @return array
     */
    public function convert(array $transactions): array
    {
        app('log')->debug('Convert all transactions into pseudo-transactions.');
        $result = [];
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $result[] = $this->convertSingle($transaction);
        }
        app('log')->debug('Done converting all transactions into pseudo-transactions.');
        print_r($result);exit;
        return $result;
    }

    /**
     * @param Transaction $transaction
     *
     * @return array
     */
    private function convertSingle(Transaction $transaction): array
    {
        app('log')->debug('Convert single transaction into pseudo-transaction.');
        $result          = [
            'transactions' => [],
        ];
        $configuredRoles = $this->getConfiguredRoles();
        $allRoles = $this->configuration->getRoles();
        $count           = $transaction->countSplits();
        $count           = 0 === $count ? 1 : $count; // add at least one transaction:

        for ($i = 0; $i < $count; $i++) {
            // loop all available roles, see if they're configured and if so, get the associated field
            // from the transaction.
            // some roles can be configured multiple times, so the $current array may hold multiple values.
            // the final response to this may be to join these fields or only use the last one.
            $current = [];
            foreach(array_keys(config('camt.fields')) as $field) {
                $role = $allRoles[$field] ?? '_ignore';
                if('_ignore' !== $role) {
                    app('log')->debug(sprintf('Field "%s" was given role "%s".', $field, $role));
                }
                if('_ignore' === $role) {
                    app('log')->debug(sprintf('Field "%s" is ignored!', $field));
                }
                $value = trim($transaction->getField($field));
                if('' !== $value) {
                    $current[$role][] = $value;
                }
            }
            $result['transactions'][] = $current;
        }

        return $result;
    }

    private function getConfiguredRoles(): array
    {
        return array_unique(array_values($this->configuration->getRoles()));
    }

}
