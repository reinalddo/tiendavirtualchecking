CREATE DATABASE tienda_web;
USE tienda_web;

-- Tabla para usuarios (tanto admin como clientes)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE, -- Reducimos la longitud para asegurar compatibilidad
    PASSWORD VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'cliente') NOT NULL DEFAULT 'cliente',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    avatar_url VARCHAR(255) NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla para categorías de productos
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    mostrar_en_inicio BOOLEAN NOT NULL DEFAULT 0
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla para los productos
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion_html TEXT,
    precio DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    es_activo BOOLEAN NOT NULL DEFAULT 1,
    es_fisico BOOLEAN NOT NULL DEFAULT 1, -- Para saber si requiere envío
    mapa_ubicacion TEXT, -- Para el código embed de Google Maps
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla PIVOTE para relacionar productos con categorías (un producto puede estar en varias categorías)
CREATE TABLE producto_categorias (
    producto_id INT,
    categoria_id INT,
    PRIMARY KEY (producto_id, categoria_id),
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla para la galería de cada producto (imágenes y videos)
CREATE TABLE producto_galeria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    tipo ENUM('imagen', 'youtube', 'video_archivo') NOT NULL,
    url VARCHAR(255) NOT NULL, -- URL de la imagen, ID de YouTube o ruta del video
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla para la sección de Preguntas y Respuestas
CREATE TABLE preguntas_respuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    pregunta TEXT NOT NULL,
    respuesta TEXT,
    fecha_pregunta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_respuesta TIMESTAMP NULL,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla para los pedidos
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    direccion_envio TEXT,
    total DECIMAL(10, 2) NOT NULL,
    estado ENUM('Pendiente de Pago', 'Pagado', 'Cancelado', 'Enviado') NOT NULL DEFAULT 'Pendiente de Pago',
    metodo_pago VARCHAR(50),
    fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla para el detalle de cada pedido
CREATE TABLE pedido_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabla para los comprobantes de pago (si el pago es manual)
CREATE TABLE comprobantes_pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    url_comprobante VARCHAR(255) NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 1. Creamos la tabla para las monedas
CREATE TABLE monedas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL, -- Ejemplo: "Dólar Americano"
    codigo VARCHAR(10) NOT NULL, -- Ejemplo: "USD"
    simbolo VARCHAR(5) NOT NULL -- Ejemplo: "$"
);

-- 2. Insertamos algunas monedas como ejemplo
INSERT INTO monedas (nombre, codigo, simbolo) VALUES
('Dólar Americano', 'USD', '$'),
('Bolívar Digital', 'VES', 'Bs.'),
('Peso Colombiano', 'COP', 'COP$');

-- 3. Añadimos la columna de moneda a la tabla de productos
ALTER TABLE productos
ADD COLUMN moneda_id INT NOT NULL DEFAULT 1 AFTER precio; -- "1" es el ID del USD por defecto

-- 4. Creamos la relación (llave foránea)
ALTER TABLE productos
ADD CONSTRAINT fk_moneda
FOREIGN KEY (moneda_id) REFERENCES monedas(id);

CREATE TABLE configuraciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre_setting VARCHAR(100) NOT NULL UNIQUE,
  valor_setting TEXT
);

CREATE TABLE hero_gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    imagen_url VARCHAR(255) NOT NULL,
    titulo VARCHAR(255),
    subtitulo TEXT,
    enlace_url VARCHAR(255),
    orden INT DEFAULT 0,
    es_activo BOOLEAN NOT NULL DEFAULT 1
);

CREATE TABLE resenas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    calificacion INT NOT NULL, -- Un número del 1 al 5
    comentario TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    es_aprobada BOOLEAN NOT NULL DEFAULT 1, -- Para futura moderación
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE cupones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    tipo_descuento ENUM('porcentaje', 'fijo') NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    fecha_expiracion DATE,
    usos_maximos INT DEFAULT 0,
    usos_actuales INT DEFAULT 0,
    es_activo BOOLEAN NOT NULL DEFAULT 1
);


CREATE TABLE auth_tokens (
    id INT(11) NOT NULL AUTO_INCREMENT,
    selector CHAR(24) NOT NULL,
    hashed_validator CHAR(64) NOT NULL,
    user_id INT(11) NOT NULL,
    expires_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY selector (selector),
    KEY user_id (user_id),
    CONSTRAINT auth_tokens_ibfk_1 
        FOREIGN KEY (user_id) 
        REFERENCES usuarios (id) 
        ON DELETE CASCADE
) ENGINE=MYISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertamos el campo para el mapa principal del negocio
INSERT INTO configuraciones (nombre_setting, valor_setting) 
VALUES ('mapa_principal', 'Aquí pega el iframe de tu mapa principal');


-- 1. Modificar la tabla de productos para que el precio base sea siempre en USD
ALTER TABLE productos DROP FOREIGN KEY fk_moneda;
ALTER TABLE productos DROP COLUMN moneda_id;
ALTER TABLE productos CHANGE precio precio_usd DECIMAL(10, 2) NOT NULL;

-- 2. Mejorar la tabla de monedas
ALTER TABLE monedas ADD COLUMN es_activa BOOLEAN NOT NULL DEFAULT 0 AFTER simbolo;
ALTER TABLE monedas ADD COLUMN tasa_conversion DECIMAL(10, 4) NOT NULL DEFAULT 1.0000 AFTER es_activa;

-- 3. Actualizar monedas existentes (EJEMPLO)
SET SQL_SAFE_UPDATES = 0;
UPDATE monedas SET es_activa = 1, tasa_conversion = 1.0000 WHERE codigo = 'USD';
UPDATE monedas SET es_activa = 1, tasa_conversion = 4000.0000 WHERE codigo = 'COP';
UPDATE monedas SET es_activa = 1, tasa_conversion = 36.5000 WHERE codigo = 'VES';
SET SQL_SAFE_UPDATES = 1;

-- 4. Modificar la tabla de pedidos para registrar la moneda y tasa de la compra
ALTER TABLE pedidos ADD COLUMN moneda_pedido VARCHAR(5) NOT NULL DEFAULT 'USD' AFTER total;
ALTER TABLE pedidos ADD COLUMN tasa_conversion_pedido DECIMAL(10, 4) NOT NULL DEFAULT 1.0000 AFTER moneda_pedido;

CREATE TABLE pedido_imagenes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pedido_detalle_id INT NOT NULL,
  imagen_url_copia VARCHAR(255) NOT NULL,
  FOREIGN KEY (pedido_detalle_id) REFERENCES pedido_detalles(id) ON DELETE CASCADE
);

-- 1. Eliminar la columna de mapa individual de los productos
ALTER TABLE productos DROP COLUMN mapa_ubicacion;

-- 2. Añadir una nueva columna para activar/desactivar el mapa principal
ALTER TABLE productos ADD COLUMN mostrar_mapa_principal BOOLEAN NOT NULL DEFAULT 0 AFTER es_fisico;

-- 3. Asegurarnos de que el mapa principal esté en la configuración
INSERT IGNORE INTO configuraciones (nombre_setting, valor_setting) 
VALUES ('mapa_principal', 'Aquí pega el iframe de tu mapa principal');

-- 1. Eliminar la columna de la tabla de productos
ALTER TABLE productos DROP COLUMN mostrar_mapa_principal;

-- 2. Añadir la nueva configuración global (0 = no, 1 = sí)
INSERT INTO configuraciones (nombre_setting, valor_setting) 
VALUES ('mostrar_mapa_en_productos', '1');

CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    producto_id INT NOT NULL,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    UNIQUE KEY (usuario_id, producto_id) -- Evita que se añada el mismo producto dos veces
);

CREATE TABLE media_library (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_archivo VARCHAR(255) NOT NULL,
    tipo_archivo VARCHAR(50) NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE media_library
ADD COLUMN alt_text VARCHAR(255) NULL AFTER nombre_archivo;

-- 1. Cambiar la columna 'nombre' a 'nombre_pila' (primer nombre)
ALTER TABLE usuarios CHANGE nombre nombre_pila VARCHAR(100) NOT NULL;

-- 2. Añadir la columna para el apellido
ALTER TABLE usuarios ADD COLUMN apellido VARCHAR(100) NULL AFTER nombre_pila;

-- 3. Añadir la columna para el teléfono
ALTER TABLE usuarios ADD COLUMN telefono VARCHAR(25) NULL AFTER PASSWORD;

-- 4. Añadir la columna para aceptar marketing
ALTER TABLE usuarios ADD COLUMN acepta_marketing BOOLEAN NOT NULL DEFAULT 0 AFTER telefono;

ALTER TABLE usuarios ADD COLUMN avatar_manual VARCHAR(255) NULL AFTER avatar_url;

ALTER TABLE categorias
ADD COLUMN codigo VARCHAR(50) NULL UNIQUE AFTER nombre;

ALTER TABLE usuarios
ADD COLUMN rif_cedula VARCHAR(20) NULL AFTER apellido,
ADD COLUMN direccion TEXT NULL AFTER telefono;

ALTER TABLE media_library MODIFY nombre_archivo VARCHAR(255) NOT NULL;

ALTER TABLE producto_galeria ADD COLUMN orden INT NOT NULL DEFAULT 0;

ALTER TABLE productos ADD COLUMN precio_descuento DECIMAL(10, 2) NULL AFTER precio_usd;

INSERT INTO configuraciones (nombre_setting, valor_setting) VALUES ('metodos_pago_activos', 'ambos');

ALTER TABLE comprobantes_pago
ADD COLUMN estado ENUM('pendiente', 'aprobado', 'rechazado') NOT NULL DEFAULT 'pendiente';

ALTER TABLE pedidos ADD COLUMN cupon_usado VARCHAR(50) NULL AFTER tasa_conversion_pedido;

ALTER TABLE productos ADD FULLTEXT(nombre, descripcion_html);

INSERT INTO configuraciones (nombre_setting, valor_setting) VALUES
('tienda_razon_social', 'Mi Tienda Web C.A.'),
('tienda_rif', 'J-12345678-9'),
('tienda_domicilio_fiscal', 'Avenida Principal, Edificio Centro, Piso 1, Oficina 101, San Cristóbal, Táchira, Venezuela'),
('tienda_telefono', '+58 276-1234567'),
('iva_porcentaje', '16.00');
INSERT INTO configuraciones (nombre_setting, valor_setting) VALUES ('tienda_logo', '');

ALTER TABLE pedidos ADD COLUMN iva_total DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER total;

ALTER TABLE cupones ADD COLUMN monto_minimo_compra DECIMAL(10, 2) NULL DEFAULT 0.00 AFTER valor;

-- 1. Tabla para gestionar cada conversación (una por pedido)
CREATE TABLE conversaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    cliente_id INT NOT NULL,
    admin_id INT NOT NULL,
    cliente_puede_responder BOOLEAN NOT NULL DEFAULT 1, -- 1 = Sí, 0 = No
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Tabla para guardar cada mensaje individual
CREATE TABLE mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversacion_id INT NOT NULL,
    remitente_id INT NOT NULL, -- El ID del usuario que envió el mensaje
    mensaje TEXT NOT NULL,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    leido BOOLEAN NOT NULL DEFAULT 0,
    FOREIGN KEY (conversacion_id) REFERENCES conversaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (remitente_id) REFERENCES usuarios(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE conversaciones DROP FOREIGN KEY conversaciones_ibfk_3;
ALTER TABLE conversaciones DROP COLUMN admin_id;

ALTER TABLE mensajes ADD COLUMN archivo_adjunto VARCHAR(255) NULL DEFAULT NULL, ADD COLUMN nombre_original_adjunto VARCHAR(255) NULL DEFAULT NULL;

CREATE TABLE notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    mensaje VARCHAR(255) NOT NULL,
    url VARCHAR(255),
    leida BOOLEAN NOT NULL DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `categorias` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `comprobantes_pago` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `configuraciones` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `conversaciones` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `cupones` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `hero_gallery` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `media_library` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `mensajes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `monedas` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `notificaciones` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pedido_detalles` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pedido_imagenes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pedidos` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `preguntas_respuestas` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `producto_categorias` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `producto_galeria` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `productos` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `resenas` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `usuarios` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `wishlist` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


ALTER TABLE `cupones` 
MODIFY `codigo` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
MODIFY `tipo_descuento` ENUM('porcentaje','fijo') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;


ALTER TABLE `productos`
ADD COLUMN `slug` VARCHAR(191) NULL UNIQUE AFTER `nombre`;

ALTER TABLE `categorias`
ADD COLUMN `slug` VARCHAR(191) NULL UNIQUE AFTER `nombre`;
