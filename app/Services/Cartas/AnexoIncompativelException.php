<?php

namespace App\Services\Cartas;

use RuntimeException;

/**
 * Anexo em PDF que não pôde ser processado (rasterizado) para aplicar o
 * papel timbrado — binário de rasterização ausente, PDF corrompido, etc.
 */
class AnexoIncompativelException extends RuntimeException {}
