<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAdmin();


$report = $_GET['report'] ?? '';

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');


/*
|--------------------------------------------------------------------------
| Expired stock (always visible)
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    'SELECT 
        m.name,
        b.batch_number,
        b.quantity,
        b.expiry_date
     FROM batches b
     JOIN medicines m 
        ON m.id = b.medicine_id
     WHERE b.quantity > 0
     AND b.expiry_date < CURDATE()
     ORDER BY b.expiry_date ASC'
);

mysqli_stmt_execute($stmt);

$expired = mysqli_fetch_all(
    mysqli_stmt_get_result($stmt),
    MYSQLI_ASSOC
);

mysqli_stmt_close($stmt);



/*
|--------------------------------------------------------------------------
| Detail reports
|--------------------------------------------------------------------------
*/

$data = [];


if ($report !== '') {


    switch ($report) {


        case 'daily':

            $sql = '
            SELECT 
                DATE(sale_date) period,
                SUM(total_amount) total
            FROM sales
            WHERE DATE(sale_date) BETWEEN ? AND ?
            GROUP BY DATE(sale_date)
            ORDER BY period DESC';

            break;



        case 'weekly':

            $sql = '
            SELECT
                YEAR(sale_date) year,
                WEEK(sale_date,1) week,
                SUM(total_amount) total
            FROM sales
            WHERE DATE(sale_date) BETWEEN ? AND ?
            GROUP BY YEAR(sale_date), WEEK(sale_date,1)
            ORDER BY year DESC, week DESC';

            break;



        case 'monthly':

            $sql = '
            SELECT
                DATE_FORMAT(sale_date,"%Y-%m") period,
                SUM(total_amount) total
            FROM sales
            WHERE DATE(sale_date) BETWEEN ? AND ?
            GROUP BY DATE_FORMAT(sale_date,"%Y-%m")
            ORDER BY period DESC';

            break;



        case 'yearly':

            $sql = '
            SELECT
                YEAR(sale_date) period,
                SUM(total_amount) total
            FROM sales
            WHERE DATE(sale_date) BETWEEN ? AND ?
            GROUP BY YEAR(sale_date)
            ORDER BY period DESC';

            break;



        case 'top':

            $sql = '
            SELECT
                m.name,
                SUM(si.quantity) sold,
                SUM(si.quantity * si.unit_price) revenue
            FROM sale_items si
            JOIN sales s
                ON s.id = si.sale_id
            JOIN medicines m
                ON m.id = si.medicine_id
            WHERE DATE(s.sale_date) BETWEEN ? AND ?
            GROUP BY m.id
            ORDER BY sold DESC
            LIMIT 10';

            break;


        default:
            $sql = '';
    }



    if ($sql !== '') {


        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            'ss',
            $from,
            $to
        );


        mysqli_stmt_execute($stmt);


        $data = mysqli_fetch_all(
            mysqli_stmt_get_result($stmt),
            MYSQLI_ASSOC
        );


        mysqli_stmt_close($stmt);
    }
}


require_once __DIR__ . '/includes/header.php';

?>



<?php if ($report !== ''): ?>


    <section class="page-header">

        <h1>
            <?php echo ucfirst($report); ?> Report
        </h1>


        <a
            class="btn secondary"
            href="reports.php">

            ← Back

        </a>


    </section>




    <form class="panel form-grid" method="get">


        <input
            type="hidden"
            name="report"
            value="<?php echo h($report); ?>">



        <div class="form-group">

            <label>From</label>

            <input
                type="date"
                name="from"
                value="<?php echo h($from); ?>">

        </div>




        <div class="form-group">

            <label>To</label>

            <input
                type="date"
                name="to"
                value="<?php echo h($to); ?>">

        </div>



        <div class="form-group">

            <label>&nbsp;</label>

            <button class="btn">
                Apply
            </button>

        </div>


    </form>




    <div class="panel">


        <table>

            <thead>

                <tr>


                    <?php if ($report === 'top'): ?>

                        <th>Medicine</th>
                        <th>Sold</th>
                        <th>Revenue</th>


                    <?php else: ?>


                        <th>Period</th>
                        <th>Total</th>


                    <?php endif; ?>


                </tr>

            </thead>



            <tbody>


                <?php foreach ($data as $row): ?>


                    <tr>


                        <?php if ($report === 'top'): ?>


                            <td>
                                <?php echo h($row['name']); ?>
                            </td>


                            <td>
                                <?php echo h($row['sold']); ?>
                            </td>


                            <td>
                                <?php echo h(format_price($row['revenue'])); ?>
                            </td>



                        <?php else: ?>


                            <td>

                                <?php

                                if (isset($row['week'])) {
                                    echo 'Week ' . $row['week'] . ' / ' . $row['year'];
                                } else {
                                    echo h($row['period']);
                                }

                                ?>

                            </td>



                            <td>
                                <?php echo h(format_price($row['total'])); ?>
                            </td>



                        <?php endif; ?>


                    </tr>



                <?php endforeach; ?>


            </tbody>


        </table>


    </div>




<?php else: ?>




    <section class="page-header">

        <h1>Reports</h1>

    </section>




    <div class="stats-grid">



        <a class="stat-card"
            href="reports.php?report=daily">

            <h3>Daily Sales</h3>

        </a>




        <a class="stat-card"
            href="reports.php?report=weekly">

            <h3>Weekly Sales</h3>

        </a>




        <a class="stat-card"
            href="reports.php?report=monthly">

            <h3>Monthly Sales</h3>

        </a>




        <a class="stat-card"
            href="reports.php?report=yearly">

            <h3>Yearly Sales</h3>

        </a>




        <a class="stat-card"
            href="reports.php?report=top">

            <h3>Top Selling</h3>

        </a>



    </div>





<?php endif; ?>




<!-- ALWAYS SHOW EXPIRED -->

<div class="panel">


    <h2>Expired Stock</h2>


    <table>


        <thead>

            <tr>

                <th>Medicine</th>
                <th>Batch</th>
                <th>Quantity</th>
                <th>Expiry</th>

            </tr>

        </thead>



        <tbody>


            <?php foreach ($expired as $row): ?>


                <tr>

                    <td>
                        <?php echo h($row['name']); ?>
                    </td>


                    <td>
                        <?php echo h($row['batch_number']); ?>
                    </td>


                    <td>
                        <?php echo h($row['quantity']); ?>
                    </td>


                    <td>
                        <?php echo h($row['expiry_date']); ?>
                    </td>


                </tr>


            <?php endforeach; ?>



        </tbody>


    </table>


</div>



<?php require_once __DIR__ . '/includes/footer.php'; ?>