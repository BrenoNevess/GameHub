<?php
class AuthRepository {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function createUser($name, $email, $passwordHash) {
        $this->pdo->beginTransaction();
        try {
            $user = $this->pdo->prepare(
                'INSERT INTO api_users (name, email, password_hash) VALUES (:name, :email, :password_hash)'
            );
            $user->execute([
                ':name' => $name,
                ':email' => $email,
                ':password_hash' => $passwordHash,
            ]);
            $userId = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();
            return $userId;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($e->getCode() === '23000') {
                throw new ApiException('Já existe uma conta com este e-mail.', 409);
            }
            throw $e;
        }
    }

    public function findByEmail($email) {
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, password_hash FROM api_users WHERE email = :email LIMIT 1'
        );
        $statement->execute([':email' => $email]);
        return $statement->fetch();
    }

    public function createToken($userId, $plainToken) {
        $statement = $this->pdo->prepare(
            'INSERT INTO api_tokens (user_id, token_hash, expires_at)
             VALUES (:user_id, :token_hash, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 30 DAY))'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':token_hash' => hash('sha256', $plainToken),
        ]);
    }

    public function userIdForToken($plainToken) {
        $statement = $this->pdo->prepare(
            'SELECT user_id FROM api_tokens
             WHERE token_hash = :token_hash AND revoked_at IS NULL AND expires_at > CURRENT_TIMESTAMP
             LIMIT 1'
        );
        $statement->execute([':token_hash' => hash('sha256', $plainToken)]);
        $userId = $statement->fetchColumn();
        return $userId === false ? null : (int) $userId;
    }

    public function revokeToken($plainToken) {
        $statement = $this->pdo->prepare(
            'UPDATE api_tokens SET revoked_at = CURRENT_TIMESTAMP
             WHERE token_hash = :token_hash AND revoked_at IS NULL'
        );
        $statement->execute([':token_hash' => hash('sha256', $plainToken)]);
    }
}
