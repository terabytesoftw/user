<?php

declare(strict_types=1);

namespace Yii\Extension\User\Repository;

use Yii\Extension\User\ActiveRecord\Token;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use Yiisoft\ActiveRecord\ActiveQuery;
use Yiisoft\ActiveRecord\ActiveQueryInterface;
use Yiisoft\ActiveRecord\ActiveRecordInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Security\Random;

final class RepositoryToken
{
    private ConnectionInterface $db;
    private LoggerInterface $logger;
    private Token $token;
    private ?ActiveQuery $tokenQuery = null;

    public function __construct(ConnectionInterface $db, LoggerInterface $logger, Token $token)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->token = $token;
        $this->tokenQuery();
    }

    public function findTokenByCondition(array $condition): ?ActiveRecordInterface
    {
        return $this->tokenQuery()->findOne($condition);
    }

    public function findTokenById(string $id): ?ActiveRecordInterface
    {
        return $this->findTokenByCondition(['user_id' => (int) $id]);
    }

    public function findTokenByParams(string $id, string $code, int $type): ?ActiveRecordInterface
    {
        return $this->findTokenByCondition(['user_id' => (int) $id, 'code' => $code, 'type' => $type]);
    }

    public function register(string $id, int $token): bool
    {
        $result = false;

        if ($this->token->getIsNewRecord() === false) {
            throw new RuntimeException('Calling "' . __CLASS__ . '::' . __METHOD__ . '" on existing user');
        }

        /** @psalm-suppress UndefinedInterfaceMethod */
        $transaction = $this->db->beginTransaction();

        try {
            $this->token->setAttribute('user_id', (int) $id);
            $this->token->setAttribute('type', $token);
            $this->token->setAttribute('created_at', time());
            $this->token->setAttribute('code', Random::string());

            if (!$this->token->save()) {
                $transaction->rollBack();
            } else {
                $transaction->commit();

                $result = true;
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            $this->logger->log(LogLevel::WARNING, $e->getMessage());
            throw $e;
        }

        return $result;
    }

    private function tokenQuery(): ActiveQueryInterface
    {
        return $this->tokenQuery = new ActiveQuery(Token::class, $this->db);
    }
}
