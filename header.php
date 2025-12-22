<?php
function page($file) {
    return basename($_SERVER['PHP_SELF']) === $file ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>FWG Admin Portal</title>

    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css">

    <style>
        :root {
            --weiz-purple: #4B236A;
            --weiz-purple-light: #A97FC8;
            --weiz-purple-line: #6A3B8C;
            --text-dark: #333;
            --bg-light: #f7f7f7;
        }

        html, body {
            height: 100%;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: var(--bg-light);
            padding-top: 90px;
        }


        .navbar-weiz {
            background: white;
            border-bottom: 2px solid var(--weiz-purple-line);
        }

        .navbar-weiz .nav-link {
            color: var(--weiz-purple);
            font-weight: 600;
            text-transform: uppercase;
            padding: 14px 20px;
            border-bottom: 3px solid transparent;
            transition: 0.2s;
            letter-spacing: 0.5px;
        }

        .navbar-weiz .nav-link:hover {
            border-bottom: 3px solid var(--weiz-purple-light);
        }

        .navbar-weiz .nav-link.active {
            border-bottom: 3px solid var(--weiz-purple);
        }



        .nav-logo {
            height: 28px;
            width: auto;
            opacity: 0.9;
            transition: 0.2s;
        }

        .nav-logo:hover {
            opacity: 1;
        }

    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-weiz fixed-top">
    <div class="container">

        <a class="navbar-brand fw-bold" href="/" style="color: var(--weiz-purple);">
            FWG Admin Portal
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainMenu">

            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item">
                    <a class="nav-link <?= page('index.php') ?>" href="index.php">Dashboard</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= page('devices.php') ?>" href="devices.php">Geräte</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= page('customers.php') ?>" href="customers.php">Kunden</a>
                </li>

               <!-- <li class="nav-item">
                    <a class="nav-link <?= page('regions.php') ?>" href="regions.php">Regionen</a>
                </li>-->

            </ul>


            <img src="/logo.svg" alt="FWG Logo" class="nav-logo ms-3">
        </div>



    </div>
</nav>

<main class="flex-fill container mt-4">
