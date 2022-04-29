<!DOCTYPE html>
<html lang="en">
    <head>
        <?php
            require_once ('partials/widgets.php');
            HeaderWidget('Error');
        ?>
    </head>

    <body>
        <?php 
            session_start();
            Navbar();
        ?>

        <div class="container-fluid d-flex flex-grow-1 justify-content-center align-items-center">
            <div class="text-center">
                <?php
                if (isset($_SESSION['error'])) {
                    $error_text = htmlspecialchars($_SESSION['error']);
                    if ($error_text == 'No records match the request') {
                        echo '<img src="public/images/no_results.png" width="300px" alt="No results found" class="m-1">';
                        echo '<p><h4>Sorry! No results found.</h4></p>';
                        echo '<p><h5>Your search did not match any results. Please try another way.</h5></p>';
                    } else {
                        echo "<p>$error_text</p>";
                    }

                }
                ?>
                <p><a role="button" class="btn btn-danger mt-4" onclick="history.back()">Go Back</a></p>
            </div>
        </div>

        <?php FooterWidget('public/images/beatyLogo.png'); ?>
    </body>
</html>