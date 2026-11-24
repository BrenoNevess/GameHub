<?php
class RawgClient {
    private $apiKey;

    public function __construct($apiKey) {
        $this->apiKey = trim($apiKey);
    }

    public function search(array $filters) {
        return $this->request('/games', $filters);
    }

    public function game($id) {
        return $this->request('/games/' . $id);
    }

    private function request($path, array $parameters = []) {
        if ($this->apiKey === '') {
            throw new ApiException('A chave RAWG_API_KEY não está configurada no servidor.', 503);
        }
        if (!function_exists('curl_init')) {
            throw new ApiException('A extensão cURL do PHP não está habilitada.', 500);
        }

        $parameters['key'] = $this->apiKey;
        $url = 'https://api.rawg.io/api' . $path . '?' . http_build_query($parameters);
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => 'GameLibrary/1.0',
        ]);

        $body = curl_exec($curl);
        $curlErrorNumber = curl_errno($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if ($body === false) {
            error_log('RAWG request failed with cURL error ' . $curlErrorNumber);
            throw new ApiException('Não foi possível consultar a RAWG.', 502);
        }
        if ($status < 200 || $status >= 300) {
            error_log('RAWG returned HTTP ' . $status);
            throw new ApiException(
                $status === 404 ? 'Jogo não encontrado na RAWG.' : 'A RAWG não conseguiu atender à consulta.',
                $status === 404 ? 404 : 502
            );
        }

        $data = json_decode($body, true);
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException('A RAWG retornou uma resposta inválida.', 502);
        }
        return $data;
    }
}
