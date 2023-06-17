<?php

namespace App\Tablas;

use PDO;

class Factura extends Modelo
{
    protected static string $tabla = 'facturas';

    public $id;
    public $created_at;
    public $usuario_id;
    public $cupon_id;
    private $total;

    public function __construct(array $campos)
    {
        $this->id = $campos['id'];
        $this->created_at = $campos['created_at'];
        $this->usuario_id = $campos['usuario_id'];
        $this->total = isset($campos['total']) ? $campos['total'] : null;
        $this->cupon_id = isset($campos['cupon_id']) ? $campos['cupon_id'] : null;
    }

    public static function existe(int $id, ?PDO $pdo = null): bool
    {
        return static::obtener($id, $pdo) !== null;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function getUsuarioId()
    {
        return $this->usuario_id;
    }

    public function getCuponId()
    {
        return $this->cupon_id;
    }

    public function getCuponNombre(?PDO $pdo = null)
    {
        $pdo = $pdo ?? conectar();

        $sent = $pdo->prepare("SELECT c.cupon FROM cupones c WHERE c.id = :id");
        $sent->execute([':id' => $this->cupon_id]);
        $res = $sent->fetchColumn();

        if ($res) {
            return $res;
        }
        return '' ;
    }

    public function getTotalGuardado()
    {
        return $this->total;
    }

    public static function get_total_guardado($total)
    {
        return $total;
    }

    public function getTotal(?PDO $pdo = null)
    {
        $pdo = $pdo ?? conectar();

        if (!isset($this->total)) {
            $sent = $pdo->prepare('SELECT SUM(cantidad * precio) AS total
                                     FROM articulos_facturas l
                                     JOIN articulos a
                                       ON l.articulo_id = a.id
                                    WHERE factura_id = :id');
            $sent->execute([':id' => $this->id]);
            $this->total = $sent->fetchColumn();
        }

        return $this->total;
    }

    public static function todosConTotalGuardado(
        array $where = [],
        array $execute = [],
        ?PDO $pdo = null
    ) {

        $pdo = $pdo ?? conectar();
        $where = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';


        $sent = $pdo->prepare("SELECT f.*, total
                                 FROM facturas f
                                 JOIN articulos_facturas l
                                   ON l.factura_id = f.id
                                 JOIN articulos a
                                   ON l.articulo_id = a.id
                               $where
                             GROUP BY f.id");
        $sent->execute($execute);
        $filas = $sent->fetchAll(PDO::FETCH_ASSOC);
        $res = [];
        foreach ($filas as $fila) {
            $res[] = new static($fila);
        }
        return $res;
    }

    public static function todosConTotal(
        array $where = [],
        array $execute = [],
        ?PDO $pdo = null
    ): array {
        $pdo = $pdo ?? conectar();

        $where = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sent = $pdo->prepare("SELECT f.*, SUM(cantidad * precio) AS total
                                 FROM facturas f
                                 JOIN articulos_facturas l
                                   ON l.factura_id = f.id
                                 JOIN articulos a
                                   ON l.articulo_id = a.id
                               $where
                             GROUP BY f.id");
        $sent->execute($execute);
        $filas = $sent->fetchAll(PDO::FETCH_ASSOC);
        $res = [];
        foreach ($filas as $fila) {
            $res[] = new static($fila);
        }
        return $res;
    }

    public function getLineas(?PDO $pdo = null): array
    {
        $pdo = $pdo ?? conectar();

        $sent = $pdo->prepare('SELECT *
                                 FROM articulos_facturas
                                WHERE factura_id = :factura_id');
        $sent->execute([':factura_id' => $this->id]);
        $lineas = $sent->fetchAll(PDO::FETCH_ASSOC);
        $res = [];
        foreach ($lineas as $linea) {
            $res[] = new Linea($linea);
        }
        return $res;
    }
}
