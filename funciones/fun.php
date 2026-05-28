<?php
require_once('connection/conect.php');
    $db = new Database();
    $con = $db->conectar();

function obtenertodoslosproductos (PDO $pdo): array {
    $sql='SELECT * FROM products p LEFT JOIN product_type t ON p.type_id = t.type_id 
        ORDER BY p.product_id ASC';
    $stmt = $pdo -> prepare ($sql);
    $stmt -> execute();
    return $stmt -> fetchAll(PDO::FETCH_ASSOC);
}

function obtenerproductoporid(PDO $pdo, int $id) {
    $sql = 'SELECT * FROM products WHERE product_id = ?';
    $stmt = $pdo -> prepare ($sql);
    $stmt -> execute([$id]);
    return $stmt -> fetch(PDO::FETCH_ASSOC);
}

function insertarproducto(PDO $pdo,string $nombre, ?int $tipo_id, ?float $precio, ?string $imagen ): int|false{
    $sql = 'INSERT INTO products (product_name, type_id, product_price, image) VALUES (?,?,?,?)';
    $stmt = $pdo -> prepare ($sql);

    try {
        $stmt -> execute([$nombre,$tipo_id, $precio, $imagen]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) 
    {
        error_log('Error al insertar: '. $e->getMessage());
        return false;
    }
}
function actualizarproducto(PDO $pdo, int $id, string $nombre,?int $tipo_id, ?float $precio, ?string $imagen): bool
{
    $sql= $imagen === ''
        ? "UPDATE products SET product_name=?, type_id=?, product_price=?,
        image=NULL WHERE product_id=?"
        : "UPDATE products SET product_name=?, type_id=?, product_price=?,
        image=COALESCE(?,image) WHERE product_id=?";

    $stmt=$pdo->prepare($sql);

    try {
        if ($imagen === ''){
            $stmt-> execute([$nombre, $tipo_id, $precio, $id]);
        }
        else {
            $stmt-> execute([$nombre, $tipo_id, $precio, $imagen, $id]);
        }
        return $stmt->rowCount()>0;
    } catch (PDOException $e)
    {
        error_log('Error al actualizar:'. $e-> getMessage());
        return false;
    }
}
function eliminarproducto(PDO $pdo, int $id): bool {
    $sql='DELETE FROM products WHERE product_id=?';
    $stmt = $pdo -> prepare($sql);
    try {
        $stmt->execute ([$id]);
        return $stmt-> rowcount()>0;
    } catch (PDOException $e) {
        error_log('Error al eliminar:'. $e-> getMessage());
        return false;
    }    
}
function obtenertodoslostipos(PDO $pdo): array
{
    $sql="SELECT * FROM product_type ORDER BY type_id ASC";
    $stmt= $pdo ->prepare($sql);
    $stmt-> execute();
    return $stmt-> fetchAll(PDO::FETCH_ASSOC);
}

function creartipo(PDO $pdo, string $nombre): int|false{
    $sql = 'INSERT INTO product_type (type_nom) VALUES (?) ON DUPLICATE KEY UPDATE type_nom = VALUES(type_nom)';
    $stmt = $pdo -> prepare($sql);
    try {
        $stmt -> execute([$nombre]);
        return $pdo -> lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}
function actualizartipo(PDO $pdo, int $id, string $nombre): bool{
    $sql = 'UPDATE product_type SET type_nom = ? WHERE type_id = ?';
    $stmt = $pdo -> prepare($sql);
    try {
        $stmt -> execute([$nombre, $id]);
        return $stmt -> rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function eliminartipo(PDO $pdo, int $id): bool {
    $sql = 'DELETE FROM product_type WHERE type_id = ?';
    $stmt = $pdo -> prepare($sql);
    try {
        $stmt -> execute([$id]);
        return $stmt -> rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}