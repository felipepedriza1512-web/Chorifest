-- Migracion Fase 5 Liquidacion de turno
-- MySQL 8.0.30

CREATE TABLE turno_liquidacion (
    id_liquidacion      INT NOT NULL AUTO_INCREMENT,
    id_domiciliario     INT NOT NULL,
    liquidado_por       INT NOT NULL,
    fecha_corte         DATETIME NOT NULL,
    fecha_liquidacion   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_efectivo      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    observaciones       VARCHAR(500) DEFAULT NULL,
    anulado             TINYINT(1) NOT NULL DEFAULT 0,
    anulado_por         INT DEFAULT NULL,
    fecha_anulacion     DATETIME DEFAULT NULL,
    motivo_anulacion    VARCHAR(500) DEFAULT NULL,
    PRIMARY KEY (id_liquidacion),
    KEY idx_liq_domiciliario (id_domiciliario),
    KEY idx_liq_admin (liquidado_por),
    CONSTRAINT fk_liq_domiciliario FOREIGN KEY (id_domiciliario)
        REFERENCES domiciliario (id_usuario),
    CONSTRAINT fk_liq_admin FOREIGN KEY (liquidado_por)
        REFERENCES administrador (id_usuario),
    CONSTRAINT fk_liq_anulado_por FOREIGN KEY (anulado_por)
        REFERENCES administrador (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE turno_liquidacion_pedido (
    id_detalle          INT NOT NULL AUTO_INCREMENT,
    id_liquidacion      INT NOT NULL,
    id_pedido           INT NOT NULL,
    monto_liquidado     DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id_detalle),
    UNIQUE KEY uq_liquidacion_pedido (id_pedido),
    KEY idx_tlp_liquidacion (id_liquidacion),
    CONSTRAINT fk_tlp_liquidacion FOREIGN KEY (id_liquidacion)
        REFERENCES turno_liquidacion (id_liquidacion) ON DELETE CASCADE,
    CONSTRAINT fk_tlp_pedido FOREIGN KEY (id_pedido)
        REFERENCES pedido (id_pedido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
