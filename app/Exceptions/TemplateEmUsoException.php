<?php

namespace App\Exceptions;

use App\Models\TemplateAvaliacao;
use RuntimeException;
use Throwable;

class TemplateEmUsoException extends RuntimeException
{
    public function __construct(
        private readonly TemplateAvaliacao $template,
        ?string $message = null,
        ?Throwable $previous = null
    ) {
        $nome = $template->nome ?: 'ID ' . $template->id;

        $message ??= sprintf(
            'Nao foi possivel remover o template "%s" porque ele está sendo utilizado em uma avaliação.',
            $nome
        );

        parent::__construct($message, 0, $previous);
    }

    public function template(): TemplateAvaliacao
    {
        return $this->template;
    }
}
