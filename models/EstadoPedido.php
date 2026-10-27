<?php
/**
 * Constantes estandarizadas de los estados de pedido.
 * Coinciden 1:1 con las filas de la tabla `estado_pedido` en la base de datos.
 */
class EstadoPedido
{
    public const RECIBIDO = 1;                // Pedido recibido en el sistema
    public const ACEPTADO = 2;                // Pedido aceptado por el restaurante
    public const EN_PREPARACION = 3;          // Pedido en preparación en cocina
    public const EMPACADO = 4;                // Pedido empacado y listo para despacho
    public const EN_CAMINO = 5;               // Pedido en camino con el domiciliario
    public const ENTREGADO = 6;               // Pedido entregado al cliente
    public const CANCELADO = 7;               // Pedido cancelado
    public const PENDIENTE_VERIFICACION = 8;  // Transferencia pendiente de verificación de pago
    public const PAGO_RECHAZADO = 9;          // Pago rechazado
    public const RECHAZADO = 10;              // Pedido rechazado por el restaurante
    public const DEMORADO = 11;               // Retraso en la entrega reportado
    public const PROBLEMA = 12;               // Problema reportado con el pedido/entrega
}
