<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PHP SDK example</title>

    <link rel="stylesheet"
          href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css"
          integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7"
          crossorigin="anonymous">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <style type="text/css">
        h1 {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .action {
            width: 100%;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>PHP SDK example</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <?php if (isset($messages)) {
                foreach ($messages as $m) { ?>
                    <div class="alert alert-<?= $m['category'] ?>">
                        <?= $m['text'] ?>
                    </div>
                <?php }
            } ?>
        </div>
    </div>

    <?php if (isset($retry)) { ?>
        <div class="col-md-12">
            <div id="btmpin"></div>

        </div>
    <?php } else { ?>
        <div class="row">
            <?php if (isset($isAuthorized) && $isAuthorized) { ?>
                <div class="col-md-4">
                    <b>E-mail:</b> <?= $email ?><br/>
                    <b>User ID:</b> <?= $userID ?><br/>
                </div>
                <div class="col-md-4"></div>
                <div class="col-md-4">
                    <a href="?refresh" class="btn btn-primary action">Refresh</a>
                    <a href="?logout" class="btn btn-primary action">Log out</a>
                </div>
            <?php } else { ?>
                <div class="col-md-12">
                    <div id="btmpin"></div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>
<?php if (isset($authURL)) { ?>
    <script src="https://dd.cdn.mpin.io/mpad/mpad.js" data-authurl="<?= $authURL ?>" data-element="btmpin"></script>
<?php } ?>
</body>
</html>
