<?php
abstract class Conexao {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            $dsn = 'mysql:host=127.0.0.1;dbname=bdpoo;charset=utf8mb4;port=3306';
            $username = 'root';
            $password = '';

            try {
                self::$instance = new PDO($dsn, $username, $password);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                throw new RuntimeException('Erro ao conectar com o banco de dados.', 0, $e);
            }
        }
        return self::$instance;
    }
}