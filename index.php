<?php

require_once("connection/conect.php");
require_once("funciones/fun.php");

$db = new Database();
$pdo = $db->conectar();

$tipos = obtenertodoslostipos($pdo);

if ($pdo === null) {
    die("Error de conexion a la base de datos");
}

$accion = $_GET['accion'] ?? 'menu';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['crear'])) {
        $nombre = trim($_POST['nombre']);
        $tipo_id = isset($_POST['tipo_id']) && $_POST['tipo_id'] !== '' ? intval($_POST['tipo_id']) : NULL;
        $precio = $_POST['precio'] !== '' ? floatval($_POST['precio']) : null;
        $imagen = null;
        $alert_img = '';
        $rutadestino = __DIR__ . "/img/";

        if (!is_dir($rutadestino)) {
            mkdir($rutadestino, 0777, true);
        }

        if (isset($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $max = 3 * 1024 * 1024;


            if ($ext !== 'png') {
                $alert_img = "solo se permiten archivos .png";
            } elseif ($_FILES['image']['size'] > $max) {
                $alert_img = "El archivo no debe superar las 3 MB";
            } else {
                $nombre_img = uniqid('img') . '.png';
                if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/img/' . $nombre_img)) {
                    $imagen = $nombre_img;
                } else {
                    $alert_img = "Error al guardar la imagen en la carpeta /img/";
                }
            }
        } else {
            $alert_img = "No se cargo ninguna imagen . El producto se registro sin foto";
        }

        if ($nombre !== '' && $alert_img === '') {
            $id_nuevo = insertarProducto($pdo, $nombre, $tipo_id, $precio, $imagen);
            $mensaje = $id_nuevo ? "producto creado.ID $id_nuevo" : "Error al insertar";
        } else {
            $mensaje = "El nombre es obligatorio";
        }
    }

    // SCRIPT ACTUALIZAR
    elseif (isset($_POST['actualizar'])) {
        $id = intval($_POST['id']);
        $nombre = trim($_POST['nombre']);
        $tipo_id = isset($_POST['tipo_id']) && $_POST['tipo_id'] !== '' ? intval($_POST['tipo_id']) : NULL;
        $precio = $_POST['precio'] !== '' ? floatval($_POST['precio']) : null;
        $imagen = null;
        $alert_img = '';


        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $max = 3 * 1024 * 1024;


            if ($ext !== 'png') {
                $alert_img = "solo se permiten archivos .png";
            } elseif ($_FILES['image']['size'] > $max) {
                $alert_img = "El archivo no debe superar las 3 MB";
            } else {
                $nombre_img = uniqid('img') . '.png';
                if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/img/' . $nombre_img)) {
                    $old = obtenerproductoporid($pdo, $id);
                    if (!empty($old['image']) && file_exists(__DIR__ . '/img/' . $old['image'])) {
                        unlink(__DIR__ . '/img/' . $old['image']);
                    }
                    $imagen = $nombre_img;
                } else {
                    $alert_img = "Error al guardar la imagen en la carpeta /img/";
                }
            }
        } elseif (isset($_POST['borrar_imagen'])) {
            $imagen = '';
        } else {
            $alert_img = "No se cargo ninguna imagen . El producto se registro sin foto";
        }

        if ($id > 0 && $nombre !== '' && $alert_img === '') {
            $ok = actualizarproducto($pdo, $id, $nombre, $tipo_id, $precio, $imagen);
            $mensaje = $ok ? "Producto actualizado" : "No se encontro el ID del producto";
        } else {
            $mensaje = "Datos invalidos para actualizar";
        }
    }

    // scrip eliminar
    elseif (isset($_POST['eliminar'])) {
        $id = intval($_POST['id']);
        $ok = eliminarproducto($pdo, $id);
        $mensaje = $ok ? "producto eliminado" : "No se encontro el producto";
    }

    if (isset($_POST['crear_tipo'])) {
        $nombre_tipo = trim($_POST['nombre_tipo']);
        if ($nombre_tipo !== '') {
            $res = creartipo($pdo, $nombre_tipo);
            $mensaje = $res ? 'Creado correctamente' : "Error al crear tipo";
        }
    } elseif (isset($_POST['actualizar_tipo'])) {
        $id_tipo = intval($_POST['id_tipo']);
        $nombre_tipo = trim($_POST['nombre_tipo']);
        if ($id_tipo > 0) {
            $res = actualizartipo($pdo, $id_tipo, $nombre_tipo);
            $mensaje = $res ? 'Tipo actualizado' : 'Error al actualizar';
        }
    } elseif (isset($_POST['eliminar_tipo'])) {
        $id_tipo = intval($_POST['id_tipo']);
        if ($id_tipo > 0) {
            $res = eliminartipo($pdo, $id_tipo);
            $mensaje = $res ? "Tipo eliminado" : "Error al eliminar";
        }
    }
}

if ($accion === "listar") {
    $productos = obtenerTodosLosProductos($pdo);
}

// BUSCAR PRODUCTO PARA EDITAR
$producto_editar = null;
if ($accion === 'editar_form') {
    $id_buscar = intval($_GET['id'] ?? 0);
    if ($id_buscar > 0) {
        $producto_editar = obtenerProductoPorId($pdo, $id_buscar);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-light">
    <div class="conx    tainer py-4">
        <h1 class="mb-4 text-center"> CRUD de productos</h1>
        <?php if ($mensaje): ?>
            <p><strong><?= $mensaje ?></strong></p>
            <p><a href="?accion=menu">Volver al menu</a></strong></p>
        <?php else: ?>
            <?php if ($accion === 'menu'): ?>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Seleccione una opcion </h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><a href="?accion=listar" class="text-decoration-none text-primary">Listar todos los productos</a></li>
                            <li class="list-group-item"><a href="?accion=crear_form" class="text-decoration-none text-primary">Crear nuevo producto</a></li>
                            <li class="list-group-item"><a href="?accion=editar_form" class="text-decoration-none text-primary">Actualizar producto</a></li>
                            <li class="list-group-item"><a href="?accion=eliminar_form" class="text-decoration-none text-primary">Eliminar producto</a></li>
                            <li class="list-group-item"><a href="?accion=tipos" class="text-decoration-none text-warning">Gestionar tipos de producto</a></li>
                        </ul>
                    </div>
                </div>

            <?php elseif ($accion === 'listar'): ?>
                <h2 class="mb-3"> Listado de productos </h2>
                <?php if (count($productos ?? []) > 0): ?>
                    <table class="table table-striped table-over table-bordered align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Precio</th>
                                <th>Imagen</th>
                            </tr>
                        </thead>
                        <?php foreach ($productos ?? [] as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['product_id']) ?></td>
                                <td><?= htmlspecialchars($p['product_name']) ?></td>
                                <td><?= htmlspecialchars($p['type_nom']) ?></td>
                                <td>$<?= number_format($p['product_price'], 2) ?></td>
                                <td>
                                    <?php if ($p['image']): ?>
                                        <img src="img/<?= htmlspecialchars($p['image']) ?>"
                                            style="max-height:50px; border-radius:4px;" alt="Producto">
                                    <?php else: ?>
                                        <span class="text-muted"> Sin foto</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">No hay productos registrados</div>
                <?php endif; ?>
                <a href="?accion=menu" class="btn btn-secondary mt-3">Volver al menu</a></strong>

                <!-- ACA VIENE FORMULARIO DE INSERCCION -->

            <?php elseif ($accion === 'crear_form'): ?>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="card-title mb-3">Nuevo producto</h2>
                        <form method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="" class="form-label">Nombre producto:</label>
                                <input type="text" name="nombre" required class="form-control"><br><br>
                            </div>
                            <div class="mb-3">
                                <select name="tipo_id" id="tipo_id" class="form-select">
                                    <option value=""> Sin tipo </option>
                                    <?php foreach ($tipos as $t): ?>
                                        <option value="<?= $t['type_id'] ?>"><?= htmlspecialchars($t['type_nom']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="" class="form-label">Precio producto:</label>
                                <input type="number" step="0.01" name="precio" require class="form-control"><br><br>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Imagen (.png, max 3MB):</label>
                                <input type="file" name="image" required class="form-control" accept=".png"><br><br>
                            </div>
                            <button type="submit" name="crear" class="btn btn-primary">Guardar</button>
                            <a href="?accion=menu" class="btn btn-outline-secondary">Volver al menu</a>
                        </form>
                    </div>
                </div>


            <?php elseif ($accion === 'editar_form' && !$producto_editar): ?>
                <h2>Actualizar producto<i class="fa-solid fa-pen"></i></h2>
                <p>Ingrese el id del producto a Editar</p>
                <form action="" method="GET">
                    <input type="hidden" name="accion" value="editar_form">
                    <label for="">ID: <input type="number" name="id" require min="1"></label>
                    <button type="submit" class="btn btn-success">Buscar</button>
                </form>
                <p><a href="?accion=menu" class="btn btn-secondary mt-3">volver a menu</a></p>

            <?php elseif ($producto_editar): ?>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="card-title mn-3">Editar producto #<?= $producto_editar['product_id'] ?></h2>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Id: </label>
                                <input type="number" name="id" value="<?= $producto_editar['product_id'] ?>" ? readonly class="form-control"><br><br>
                            </div>
                            <div class="mb-3">
                                <label for="" class="form-label">Nombre: </label>
                                <input type="text" name="nombre" value="<?= $producto_editar['product_name'] ?>" require class="form-control"><br><br>
                            </div>
                            <div class="mb-3">
                                <select name="tipo_id" id="tipo_id" class="form-select">
                                    <option value=""> Sin tipo </option>
                                    <?php foreach ($tipos as $t): ?>
                                        <option value="<?= $t['type_id'] ?>" <?= $producto_editar['type_id'] == $t['type_id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['type_nom']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="" class="form-label">Precio: </label>
                                <input type="number" step="0.01" name="precio" value="<?= $producto_editar['product_price'] ?>" class="form-control"><br><br>
                            </div>
                            <div class="mb-3">
                                <label>Imagen actual</label>
                                <?php if ($producto_editar['image']): ?>
                                    <img src="img/<?= htmlspecialchars($producto_editar['image']) ?>"
                                        style="max-height: 100px; border-radius: 4px;" class="d-block mb-2">
                                    <div>
                                        <input type="checkbox" name="borrar_imagen" id="delimg" class="fore-check-input">
                                        <label class="fore-check-label" for="delimg">Eliminar imagen actual </label>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted"> Sin imagen</span><br><br>
                                <?php endif; ?>
                                <label class="form-label mt-2">Subir nueva (.png, max 3MB)</label>
                                <input type="file" name="image" class="form-control" accept=".png">
                            </div>
                            <button type="submit" name="actualizar" class="btn btn-warning">Actualizar</button>
                            <a href="?accion=menu" class="btn btn-outline-secondary">Cancelar</a>
                        </form>
                    </div>
                </div>

            <?php elseif ($accion === 'eliminar_form'): ?>
                <h2>Eliminar productos<i class="fa-solid fa-trash-can"></i></h2>
                <p>Ingrese el ID del producto a eliminar</p>
                <form method="POST" action="">
                    <label for="">ID: <input type="number" name="id" required min="1"></label><br><br>
                    <button type="submit" name="eliminar" class="btn btn-warning" onclick="return confirm('¿seguro que desea eliminar?')">Eliminar</button>
                </form>
                <p><a href="?accion=menu" class="btn btn-outline-secondary mt-3">volver a menu</a></p>




            <?php elseif ($accion === 'tipos'):
                $lista_tipos = obtenertodoslostipos($pdo);
            ?>
                <h2>Tipos de producto</h2>
                <div class="card mb-3 bg-light">
                    <div class="card-body">
                        <form method="post" class="d-flex gap-2 align-items-center">
                            <input type="text" name="nombre_tipo" class="form-control" placeholder="Nuevo tipo" required>
                            <button type="submit" name="crear_tipo" class="btn btn-success"> Agregar </button>
                        </form>
                    </div>
                </div>
                <?php if (count($lista_tipos) > 0): ?>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre del tipo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lista_tipos as $tipo): ?>
                                <tr>
                                    <td><?= $tipo['type_id'] ?></td>
                                    <td>
                                        <form method="post" class="d-flex gap-2">
                                            <input type="hidden" name="id_tipo" value="<?= $tipo['type_id'] ?>">
                                            <input type="text" name="nombre_tipo" value="<?= htmlspecialchars($tipo['type_nom']) ?>"
                                                class="form-control form-control-sm" required>
                                            <button type="submit" name="actualizar_tipo" class="btn btn-sm btn-primary">Actualizar</button>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm ('Eliminar este tipo? Los productos perderan su categoria. ');">
                                            <input type="hidden" name="id_tipo" value="<?= $tipo['type_id'] ?>">
                                            <button type="submit" name="eliminar_tipo" class="btn btn-sm btn-danger">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>NO hay tipos registrados</p>
                <?php endif; ?>

                <a href="?accion=menu" class="btn btn-secondary mt-3">Volver al menu</a>
            <?php endif; ?>
        <?php endif; ?>



</body>

</html>