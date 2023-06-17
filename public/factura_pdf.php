<?php
session_start();

use App\Tablas\Factura;
use App\Tablas\Cupon;

require '../vendor/autoload.php';

if (!($usuario = \App\Tablas\Usuario::logueado())) {
    return volver();
}

$id = obtener_get('id');

if (!isset($id)) {
    return volver();
}

$pdo = conectar();

$factura = Factura::obtener($id, $pdo);

if (!isset($factura)) {
    return volver();
}

if ($factura->getUsuarioId() != $usuario->id) {
    return volver();
}

$cupon = $factura->getCuponNombre();
$cuponId = $factura->getCuponId();

$filas_tabla = '';
$total = 0;
$string = '';
foreach ($factura->getLineas($pdo) as $linea) {
    $articulo = $linea->getArticulo();
    $id = $articulo->getId();
    $codigo = $articulo->getCodigo();
    $descripcion = $articulo->getDescripcion();
    $cantidad = $linea->getCantidad();
    $precio = $articulo->getPrecio();
    $precioInicial = '';
    $importeInicial = '';
    $usaCupon = $articulo->tieneCupon($id, $cuponId);
    print_r($precio . "-----");

    if ($cuponId && $usaCupon) {
        $precioInicial =  $precio . ' -> ';
        $importeInicial = $cantidad * $precio . ' -> ';
        $cupon = CUPON::obtenerCupon($cuponId);
        $precio = $precio - ($precio * $cupon->getDescuento());
        $string = "Usó el cupón: " . $cupon->getCupon() . " en el artículo " . $articulo->getDescripcion();
    }

    $importe = $cantidad * $precio;
    $total += $importe;
    $precio = dinero($precio);
    $importe = dinero($importe);

    $filas_tabla .= <<<EOF
        <tr>
            <td>$codigo</td>
            <td>$descripcion</td>
            <td>$cantidad</td>
            <td>$precioInicial $precio</td>
            <td>$importeInicial $importe</td>
        </tr>
    EOF;
}

$total = dinero($total);
$totalInicial = $total;

if ($cuponId) {
    $total = dinero($factura->getTotalGuardado());
}


$res = <<<EOT
<p>Factura número: {$factura->id}</p>

<table border="1" class="font-sans mx-auto">
    <tr>
        <th>Código</th>
        <th>Descripción</th>
        <th>Cantidad</th>
        <th>Precio</th>
        <th>Importe</th>
    </tr>
    <tbody>
        $filas_tabla
    </tbody>
</table>

<p>Total: $total</p>
<p>$string </p>
EOT;

// Asi lo hizo ricardo y no fuciona porque el vendor no puede modificar los archivos temporales
/* $mpdf = new \Mpdf\Mpdf(); */

// Create an instance of the class:
$mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/custom/temp/dir/path']);

// Write some HTML code:
$mpdf->WriteHTML(file_get_contents('css/output.css'), \Mpdf\HTMLParserMode::HEADER_CSS);
$mpdf->WriteHTML($res, \Mpdf\HTMLParserMode::HTML_BODY);

// Output a PDF file directly to the browser
$mpdf->Output();
