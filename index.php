<!DOCTYPE html>
<!--
 *  pit - 14 nov 2022  - 11:22:00
-->
<?php
error_reporting(E_ALL);
//error_reporting(0);
$root = filter_input(INPUT_SERVER, 'SCRIPT_NAME');
$db_name = filter_input(INPUT_GET, 'dbname', FILTER_DEFAULT);
$table = filter_input(INPUT_POST, 'table', FILTER_DEFAULT);
$query = filter_input(INPUT_POST, 'query', FILTER_DEFAULT);
$limit = filter_input(INPUT_POST, 'limit', FILTER_DEFAULT);

if ($db_name == "") {
    $db_name = filter_input(INPUT_POST, 'dbname', FILTER_DEFAULT);
}
?>
<html>
    <head>
        <meta charset="UTF-8">
        <title>SQLite management</title>
        <link href="css/style.min.css" rel="stylesheet" type="text/css" media="all">
        <link rel="icon" href="images/favicon.ico" />
    </head>
    <body>
        <div id="content" class="center">
            <h1>SQLite management</h1>
            <?php
            if ($db_name != "") {
                if (!file_exists($db_name)) {
                    $db_name = ""; // activated for not create db
                    if ($db_name != "") {
                        ?>
                        <script>
                            alert("Database created!");
                        </script>
                        <?php
                    }
                } else {
                    if (!is_writable($db_name)) {
                        ?>
                        <script>
                            alert("Database is not writable!");
                        </script>
                        <?php
                    }
                }
            }
            if ($db_name == "" && $table == "") {
                ?>
                <form name="setDb" action="<?php echo $root; ?>" method="get">
                    <input type="text" name="dbname" value="">
                    <input type="submit" title="" class="button1" value="Set db name">
                </form> 
                <?php
                die;
            }
            ?>
            <form name="close" action="<?php echo $root; ?>" method="get">
                <input type="submit" title="Refresh database" class="button1" value="Refresh database">
            </form> 
            <h2>Database: <?php echo $db_name; ?></h2>
            <?php
            //$db = new PDO("sqlite:".__DIR__."/".$db_name);
            //$db = new \PDO('sqlite:' . __DIR__ . '/' . $db_name, '', '', array(
            $db = new \PDO('sqlite:' . $db_name, '', '', array(
                \PDO::ATTR_TIMEOUT => 0,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ));
            if ($query == "") {
                if ($table == "") {
                    echo "<h2>Tables founds</h2>";
                    $sql = "SELECT name FROM sqlite_schema WHERE type='table' ORDER BY name;";
                    $stm = pdoQuery($sql);
                    $result = $stm->fetchAll(PDO::FETCH_ASSOC);
                    $numCols = $stm->columnCount();
                    $numRows = count($result);
                    ?>
                    <div id="butlist" class="center">
                        <table class="buttons">
                            <?php
                            foreach ($result as $key => $tbname) {
                                $sql = "SELECT COUNT(*) FROM " . $tbname['name'];
                                $stm_count = $db->query($sql);
                                $rows = $stm_count->fetchAll(PDO::FETCH_BOTH);
                                ?>
                                <form name="setTable" action="<?php echo $root; ?>" method="post" target="_blank">
                                    <tr>
                                    <input type="hidden" name="dbname" value="<?php echo $db_name; ?>">
                                    <input type="hidden" name="table" value="<?php echo $tbname['name']; ?>">
                                    <td>
                                        <input type="submit" title="Open table in another window" class="button1" value="<?php echo $tbname['name'] . " (rows:" . $rows[0][0] . "-col:$numCols)"; ?>">
                                    </td>
                                    <td>
                                        <label class="limit" for="limit">limit</label>
                                    </td>
                                    <td>
                                        <input type="text" name="limit" class="limit" size="8" title="Limit syntax 0,10 or 10" value="" onclick="this.select();">
                                    </td>
                                    </tr>
                                </form> 
                                <?php
                            }
                            ?>
                            <tr>
                            <form name="allTables" action="<?php echo $root; ?>" method="post" target="_blank">
                                <input type="hidden" name="dbname" value="<?php echo $db_name; ?>">
                                <input type="hidden" name="table" value="*">
                                <td>
                                    <input type="submit" title="Open all tables in another window, could be slow..." class="button1" value="Show all tables">
                                </td>
                            </form> 
                            </tr>
                        </table>
                    </div>
                    <div id="butlist2" class="center">
                        <form name="setQuery" action="<?php echo $root; ?>" method="post" target="_blank">
                            <table class="buttons" style="border: none;">
                                <input type="hidden" name="dbname" value="<?php echo $db_name; ?>">
                                <tr>
                                    <td>
                                        <textarea name="query" rows="10" title="Insert query" value="" onclick="this.select();"></textarea>
                                        <!--<input type="text" name="query" size="30" title="Set query" value="" onclick="this.select();">-->
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="submit" title="Run query" class="button1" value="Run query">
                                    </td>
                                </tr>
                            </table>
                        </form> 
                    </div>
                    <?php
                } elseif ($table == "*") {
                    $sql = "SELECT name FROM sqlite_schema WHERE type='table' ORDER BY name;";
                    $stm = $db->query($sql);
                    $result = $stm->fetchAll(PDO::FETCH_ASSOC);
                    $numCols = $stm->columnCount();
                    $numRows = count($result);
                    foreach ($result as $key => $tbname) {
                        $sql2 = "SELECT * FROM $tbname[name]";
                        $stm2 = $db->query($sql2);
                        $result2 = $stm2->fetchAll(PDO::FETCH_ASSOC);
                        $numCols2 = $stm2->columnCount();
                        $numRows2 = count($result2);
                        showTable($tbname['name'], $numCols2, $numRows2, $result2);
                    }
                } else {
                    if ($limit != "") {
                        $limit = " LIMIT " . $limit;
                    }
                    $sql2 = "SELECT * FROM $table $limit";
                    $stm2 = $db->query($sql2);
                    $result2 = $stm2->fetchAll(PDO::FETCH_ASSOC);
                    $numCols2 = $stm2->columnCount();
                    $numRows2 = count($result2);
                    showTable($table, $numCols2, $numRows2, $result2);
                }
            } else {
                //query
                $ck = substr($query, -1);
                if (substr($query, -1) != ";") {
                    $query = $query . ";";
                }
                $aquery = explode(";", $query, -1);
                /* echo "<pre>";print_r($aquery);echo "</pre>"; */
                for ($i = 0; $i < count($aquery); $i++) {
                    $sql = trim($aquery[$i]);
                    if ($sql != "") {
                        echo "<br>Runnin query:";
                        echo "<br>$sql";
                        echo "<br>";
                        $stm = $db->query($sql);
                        $result = $stm->fetchAll(PDO::FETCH_ASSOC);
                        $numCols = $stm->columnCount();
                        $numRows = count($result);
                        if ($numRows > 0) {
                            $table = "";
                            showTable($table, $numCols, $numRows, $result);
                        }
                    }
                }
            }
            ?>
        </div>
    </body>
</html>

<?php

function showTable($tbname, $numCols, $numRows, $result) {
    if ($numRows != 0 && $numCols != 0) {
        echo "<br>$tbname Rows:" . $numRows . ",Col:" . $numCols;
        echo "<br><br>";
        echo "<table class='result'>";
        $ne = 0;

        foreach ($result as $key => $fields) {
            $ne++;
            if ($ne == 1) {
                $nc = 0;
                echo "<tr class='result'>";
                foreach ($fields as $key => $value) {
                    $nc++;
                    echo "<th class='result'>";
                    echo "{$key}";
                    echo "</th class='result'>";
                }
                echo "</tr>";
            }
            echo "<tr class='result'>";
            foreach ($fields as $key => $value) {
                echo "<td class='result'>";
                $img = 0;
                if ($value != "" || $value != null) {
                    $hexvalue = bin2hex($value);
                    if (substr($hexvalue, 0, 14) == "89504e470d0a1a") {
                        $img = 1; //png
                    }
                    if (substr($hexvalue, 0, 8) == "47494638") {
                        $img = 1; //gif
                    }
                    if (substr($hexvalue, 0, 8) == "FFD8FFE0") {
                        $img = 1; //jpg
                    }
                    //echo "{$value}";
                    //echo bin2hex($value);
                    //echo "{$showphoto}";
                }
                if ($img) {
                    $showphoto = base64_encode($value);
                    ?>
                    <img  alt="no image yet" src="data:image/jpeg;base64,<?= $showphoto ?>" >
                    <?php
                } else {
                    echo "{$value}";
                }
                echo "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div> Rows not found!</div>";
    }
}

/**
 * Execute a query
 * @param type $sql
 * @return PDO Statement object
 */
function pdoQuery($sql) {
    global $db;
    try {
        $stm = $db->query($sql);
    } catch (PDOException $e) {
        $ainfo = $e->errorInfo;
        $desc = $ainfo[1] . " " . $ainfo[2];
        echo '<br>' . $desc;
        die;
    }
    return $stm;
}
