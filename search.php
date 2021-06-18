<?php

use airmoi\FileMaker\FileMakerException;
use airmoi\FileMaker\Object\Field;

require_once ('utilities.php');
require_once ('constants.php');
require_once ('DatabaseSearch.php');

session_set_cookie_params(0,'/','.ubc.ca',isset($_SERVER["HTTPS"]), true);
session_start();

define("DATABASE", $_GET['Database'] ?? null);

checkDatabaseField(DATABASE);

try {
    $databaseSearch = DatabaseSearch::fromDatabaseName(DATABASE);
} catch (FileMakerException $e) {
    $_SESSION['error'] = 'Unsupported database given';
    header('Location: error.php');
    exit;
}

# filter the layouts to those we only want
$ignoreValues = ['SortNum' => '', 'Accession Numerical' => '', 'Imaged' => '', 'IIFRNo' => '',
    'Photographs::photoFileName' => '', 'Event::eventDate' => '', 'card01' => '', 'Has Image' => '', 'imaged' => ''];

$allFieldNames = array_keys($databaseSearch->getSearchLayout()->getFields());

$allFields = $databaseSearch->getSearchLayout()->getFields();

$allFields = array_diff_key($allFields, $ignoreValues);

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <link rel="stylesheet" href="https://herbweb.botany.ubc.ca/arcgis_js_api/library/4.10/esri/css/main.css">
        <?php
            require_once('partials/widgets.php');

            HeaderWidget('Search');
            require_once('partials/conditionalCSS.php');
        ?>
        <link rel="stylesheet" href="public/css/search.css">

        <!-- scripts -->
        <script type="text/javascript" src="public/js/process.js"></script>
    </head>

    <body>
        <?php Navbar(); ?>

        <!-- Page title below navbar -->
        <?php TitleBanner(databaseName: DATABASE, paddingIndex: 3); ?>

        <div class="container-fluid flex-grow-1">
            <form action="render.php" method="get" id="submit-form">
                <!-- hidden text field containing the database name -->
                <label>
                    <input type="text" hidden id="Database" name="Database" value=<?php echo htmlspecialchars(DATABASE); ?>>
                </label>

                <!-- search or show all -->
                <div class="d-flex flex-wrap flex-column flex-md-row justify-content-evenly align-items-center p-1">
                    <!-- search or advanced search -->
                    <div class="flex-grow-1 px-sm-5 mb-4 mb-md-0" style="max-width: 75%">
                        <div class="input-group">
                            <button type="button" class="btn btn-outline-secondary order-1 order-md-0 conditional-outline-background" data-bs-toggle="collapse" data-bs-target="#advancedSearchDiv">Advanced Search</button>
                            <!-- small form for taxon search -->
                            <form action="render.php" method="get" id="taxon-search">
                                <input type="text" class="form-control form-control-lg order-0 order-md-1" style="min-width: 225px" placeholder="Start a taxon search" name="taxon-search">
                                <button type="submit" class="btn btn-outline-primary conditional-background order-2 flex-grow-1 flex-md-grow-0"> Search </button>
                            </form>
                        </div>
                        <div class="form-text">You can search for phylum, class, order, family, etc... </div>
                    </div>

                    <!-- show all button, add mb-4 to align button to search bar -->
                    <div class="mb-4">
                        <button id="form" type="button" value="submit" onclick="submitEmptyForm()" class="btn btn-primary btn-lg conditional-background">Show All Records</button>
                    </div>
                </div>

                <div class="d-flex justify-content-around align-items-center px-5 py-3">
                    <div class="collapse w-100" id="advancedSearchDiv">
                        <!--
                            form elements,
                            using flex and media queries, we have one, two or three columns
                            refer to the view css to media queries, we followed bootstrap cutoffs
                         -->
                        <div class="d-flex flex-column flex-md-row flex-md-wrap justify-content-center align-items-start align-items-md-end">
                            <?php
                            # Loop over all fields and create a field element in the form for each!
                            $count = 0;
                            /** @var string $fieldName
                              * @var Field $field */
                            foreach ($allFields as $fieldName => $field) : ?>

                                <div class="px-3 py-2 py-md-1 flex-fill responsive-columns">
                                    <!-- field name and input -->
                                    <div class="input-group">
                                        <a data-bs-toggle="collapse" href="#collapsable<?php echo $count?>" role="button">
                                            <label class="input-group-text conditional-background-light"
                                                   for="field-<?php echo $fieldName?>">
                                                <?php echo htmlspecialchars(formatField($fieldName)) ?>
                                            </label>
                                        </a>
                                        <?php
                                        # Try to get a list of options, if error (aka none available) then no datalist
                                        try {
                                            $fieldValues = $field->getValueList();
                                        } catch (FileMakerException $e) { /* Do nothing */ }

                                        if (isset($fieldValues)) : ?>
                                            <input class="form-control" list="datalistOptions" placeholder="Type to search" id="field-<?php echo $fieldName?>">
                                            <datalist id="datalistOptions">
                                                <?php foreach ($fieldValues as $fieldValue): ?>
                                                    <option value="<?=$fieldValue?>"></option>
                                                <?php endforeach; ?>
                                            </datalist>
                                        <?php else: ?>
                                            <input class="form-control" type="<?php echo $field->getResult() ?>" id="field-<?php echo $fieldName?>">
                                        <?php endif; ?>
                                    </div>
                                    <!-- field information -->
                                    <div class="collapse" id="collapsable<?php echo $count?>">
                                        <div class="card card-body">
                                            This is some information for field <?=$fieldName?>!
                                        </div>
                                    </div>
                                </div>
                                <?php $count++; endforeach; ?>
                        </div>

                        <!-- search ops and submit button -->
                        <div class="d-inline-flex justify-content-evenly align-items-center py-4 w-100">

                            <!-- radio inputs have same name, so that only one can be enabled, and is used in render.php -->
                            <div class="btn-group">
                                <span class="input-group-text"> Search with: </span>
                                <input type="radio" class="btn-check radio-conditional-background" name="operator" id="and" value="and" checked autocomplete="off">
                                <label class="btn btn-outline-secondary" for="and"> AND </label>

                                <input type="radio" class="btn-check radio-conditional-background" name="operator" id="or" value="or" autocomplete="off">
                                <label class="btn btn-outline-secondary" for="or"> OR </label>
                            </div>

                            <!-- only with image select, tooltip to explain why disabled -->
                            <div class="form-check form-switch" <?php if (!in_array(DATABASE, kDATABASES_WITH_IMAGES)) echo 'data-bs-toggle="tooltip" title="No images available"' ?>>
                                <label class="form-check-label">
                                    <input type="checkbox" class="form-check-input checkbox-conditional-background" name="hasImage" <?php if (!in_array(DATABASE, kDATABASES_WITH_IMAGES)) echo 'disabled' ?>>
                                    Only show records that contain an image
                                </label>
                            </div>

                            <!-- submit button -->
                            <div class="form-group">
                                <button type="submit" onclick="submitForm()" class="btn btn-outline-primary conditional-background"> Advanced Search </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- footer -->
        <?php FooterWidget(imgSrc: 'public/images/beatyLogo.png'); ?>

        <!-- Script to enable tooltips -->
        <script>
            let tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            let tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        </script>
    </body>
</html>