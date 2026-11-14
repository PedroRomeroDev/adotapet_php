<?php
function perdidoValidar(array $dados): array
{
    $limites = [
        'nome' => 80,
        'especie' => 30,
        'descricao' => 3000,
        'localizacao' => 200,
        'data_desaparecimento' => 10,
        'contato' => 20,
    ];

    $form = [];

    foreach ($limites as $campo => $limite) {
        $valor = $dados[$campo] ?? '';

        if (!is_string($valor) || trim($valor) === '' || mb_strlen(trim($valor)) > $limite) {
            throw new RuntimeException('Preencha todos os campos e respeite os limites de tamanho.');
        }

        $form[$campo] = trim($valor);
    }

    $data = DateTimeImmutable::createFromFormat('!Y-m-d', $form['data_desaparecimento']);

    if (!$data || $data->format('Y-m-d') !== $form['data_desaparecimento'] || $data > new DateTimeImmutable('today')) {
        throw new RuntimeException('Informe uma data de desaparecimento válida, até hoje.');
    }

    if (!preg_match('/^[+\d\s().-]+$/', $form['contato'])) {
        throw new RuntimeException('Informe um telefone válido com DDD.');
    }

    $form['contato'] = preg_replace('/\D/', '', $form['contato']);

    if (strlen($form['contato']) < 10 || strlen($form['contato']) > 13) {
        throw new RuntimeException('Informe um telefone válido com DDD.');
    }

    return $form;
}
