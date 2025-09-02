<?php
namespace App\Service;

class ConsultaCepService
{
    public static function onBuscarCep($cep)
    {
        try {
            $cep = preg_replace('/[^0-9]/', '', $cep);

            if (strlen($cep) !== 8) {
                throw new Exception('CEP inválido');
            }

            $url = "https://viacep.com.br/ws/{$cep}/json/";
            
            $resultado = @file_get_contents($url);

            if ($resultado === FALSE) {
                throw new Exception('Não foi possível conectar à API de CEP.');
            }

            $dados = json_decode($resultado);

            if (isset($dados->erro) && $dados->erro) {
                throw new Exception('CEP não encontrado.');
            }

            return $dados;
        } catch (Exception $e) {
            return (object) ['error' => $e->getMessage()];
        }
    }
}