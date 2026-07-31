<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
requireAdmin();

$pago_id = (int)($_GET['id'] ?? 0);
$db = getDB();

$pago = $db->query("
    SELECT 
        p.*,
        cu.numero as cuota_num,
        cu.fecha_vencimiento,
        c.nombre as cliente,
        c.dni,
        c.direccion,
        p2.codigo as propiedad,
        co.numero as contrato,
        e.nombre as empleado
    FROM pagos p
    JOIN cuotas cu ON p.cuota_id = cu.id
    JOIN financiaciones f ON cu.financiacion_id = f.id
    JOIN contratos co ON f.contrato_id = co.id
    JOIN clientes c ON co.cliente_id = c.id
    JOIN propiedades p2 ON co.propiedad_id = p2.id
    JOIN empleados e ON co.empleado_id = e.id
    WHERE p.id = $pago_id
")->fetch_assoc();

if(!$pago) {
    header('Location: ' . BASE . '/modules/reportes/pagos.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boleta de Pago - <?= $pago['cliente'] ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #e5e7eb;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .boleta {
            max-width: 400px;
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .boleta-header {
            background: #1e40af;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .boleta-header h1 { font-size: 20px; margin-bottom: 4px; }
        .boleta-header p { font-size: 11px; opacity: 0.8; }
        .boleta-body { padding: 20px; }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #e5e7eb;
            font-size: 13px;
        }
        .info-label { font-weight: bold; color: #4b5563; }
        .info-value { color: #111827; }
        .total-row {
            background: #f3f4f6;
            padding: 12px;
            margin-top: 16px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 16px;
        }
        .footer {
            text-align: center;
            padding: 16px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #6b7280;
        }
        .btn-print {
            display: block;
            width: calc(100% - 40px);
            margin: 0 auto 20px;
            padding: 10px;
            background: #1e40af;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-print:hover { background: #1e3a8a; }
        @media print {
            body { background: white; padding: 0; }
            .btn-print { display: none; }
            .boleta { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>
<div class="boleta">
    <div class="boleta-header">
        <h1>🏢 ORTIZ INMOBILIARIA</h1>
        <p>RUC: 20601234567 - Jr. Lima 123, Puno</p>
        <p>Tel: (051) 123456 - info@ortiz.com</p>
    </div>
    
    <div class="boleta-body">
        <div style="text-align: center; margin-bottom: 16px;">
            <strong style="font-size: 14px;">BOLETA DE PAGO N° <?= str_pad($pago['id'], 8, '0', STR_PAD_LEFT) ?></strong>
            <p style="font-size: 11px; color: #6b7280;">Fecha: <?= date('d/m/Y H:i', strtotime($pago['fecha_pago'])) ?></p>
        </div>

        <div class="info-row">
            <span class="info-label">CLIENTE:</span>
            <span class="info-value"><?= strtoupper($pago['cliente']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">DNI:</span>
            <span class="info-value"><?= $pago['dni'] ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">DIRECCIÓN:</span>
            <span class="info-value"><?= $pago['direccion'] ?></span>
        </div>
        
        <div style="margin: 16px 0; height: 1px; background: #e5e7eb;"></div>
        
        <div class="info-row">
            <span class="info-label">CONTRATO:</span>
            <span class="info-value"><?= $pago['contrato'] ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">PROPIEDAD:</span>
            <span class="info-value"><?= $pago['propiedad'] ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">N° CUOTA:</span>
            <span class="info-value"><?= $pago['cuota_num'] ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">VENCIMIENTO:</span>
            <span class="info-value"><?= date('d/m/Y', strtotime($pago['fecha_vencimiento'])) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">MÉTODO PAGO:</span>
            <span class="info-value"><?= $pago['metodo'] ?></span>
        </div>
        <?php if($pago['numero_operacion']): ?>
        <div class="info-row">
            <span class="info-label">N° OPERACIÓN:</span>
            <span class="info-value"><?= $pago['numero_operacion'] ?></span>
        </div>
        <?php endif; ?>
        
        <div class="total-row">
            <span>TOTAL PAGADO:</span>
            <span style="color: #16a34a;">S/ <?= number_format($pago['monto'], 2) ?></span>
        </div>
        
        <div style="margin-top: 20px; text-align: center; font-size: 12px;">
            <p>_________________________________</p>
            <p>Firma del Cliente</p>
        </div>
    </div>
    
    <div class="footer">
        <p>¡Gracias por confiar en Ortiz Inmobiliaria!</p>
        <p>Este documento es un comprobante de pago válido.</p>
    </div>
</div>

<button class="btn-print" onclick="window.print()">🖨️ Imprimir Boleta</button>
</body>
</html>