<?php

use airmoi\FileMaker\FileMakerException;
use airmoi\FileMaker\Object\Field;
use airmoi\FileMaker\Object\Result;

require_once('utilities.php');
require_once ('my_autoloader.php');

session_set_cookie_params(0,'/','.ubc.ca',isset($_SERVER["HTTPS"]), true);
session_start();

define("DATABASE", $_GET['Database'] ?? null);

checkDatabaseField(DATABASE);

if (isset($_SESSION['databaseSearch']) and ($_SESSION['databaseSearch'])->getName() == DATABASE) {
    $databaseSearch = $_SESSION['databaseSearch'];
} else {
    try {
        $databaseSearch = DatabaseSearch::fromDatabaseName(DATABASE);
        $_SESSION['databaseSearch'] = $databaseSearch;
    } catch (FileMakerException $e) {
        $_SESSION['error'] = 'Unsupported database given';
        header('Location: error.php');
        exit;
    }
}

# TODO let user change this number
$maxResponses = 30;

# remove any empty get fields
$usefulGETFields = array_filter($_GET);

if ($_GET['taxon-search'] ?? null) {
    try {
        $result = $databaseSearch->queryTaxonSearch($_GET['taxon-search'], $_GET['Sort'] ?? null,
            $_GET['SortOrder'] ?? null,$maxResponses, $_GET['Page'] ?? 1);
        ;
    } catch (FileMakerException $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: error.php');
        exit;
    }
} else {
    # since we are diffing by keys, we need to set dummy values
    $unUsedGETFields = ['operator' => '', 'Sort' => '', 'Page' => '', 'SortOrder' => '', 'Database' => ''];
    $usefulGETFields = array_diff_key($usefulGETFields, $unUsedGETFields);

    try {
        $result = $databaseSearch->queryForResults($maxResponses, $usefulGETFields, $_GET['operator'] ?? 'and',
            $_GET['Sort'] ?? null, $_GET['Page'] ?? 1, $_GET['SortOrder'] ?? null);
    } catch (FileMakerException $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: error.php');
        exit;
    }
}

if($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['downloadData']))
{
    createAndDownloadCSV();
}
function createAndDownloadCSV(): void
{
    $filename = "data_" . date("Y-m-d_H-i-s") . ".csv";
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'";');
    ob_end_clean();

    $downloadResult = getDownloadData();
    $file = fopen('php://output', "w");
    $fieldNames = $downloadResult->getFields();
    fputcsv($file, $fieldNames);
    foreach ($downloadResult->getRecords() as $record) {
        $row = array();
        foreach ($fieldNames as $field) {
            try {
                $row[] = $record->getField($field, 0, true);
            } catch (FileMakerException $e) {
                $row[] = '';
            }
        }
        fputcsv($file, $row);
    }

    fclose( $file );

    exit();
}

function getDownloadData(): ?Result
{
    $maxDownloadRecords = 1000;
    $usefulGETFields = array_filter($_GET);
    $databaseSearchDownload = $_SESSION['databaseSearch'];

    if ($_GET['taxon-search'] ?? null) {
        try {
            $downloadResult = $databaseSearchDownload->queryTaxonSearch($_GET['taxon-search'], $_GET['Sort'] ?? null,
                $_GET['SortOrder'] ?? null, $maxDownloadRecords, 1);
        } catch (FileMakerException $e) {
            return null;
        }
    } else {
        # since we are diffing by keys, we need to set dummy values
        $unUsedGETFields = ['operator' => '', 'Sort' => '', 'Page' => '', 'SortOrder' => '', 'Database' => ''];
        $usefulGETFields = array_diff_key($usefulGETFields, $unUsedGETFields);

        try {
            $downloadResult = $databaseSearchDownload->queryForResults($maxDownloadRecords, $usefulGETFields, $_GET['operator'] ?? 'and',
                $_GET['Sort'] ?? null, 1, $_GET['SortOrder'] ?? null);
        } catch (FileMakerException $e) {
            return null;
        }
    }

    return $downloadResult;
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <?php
            require_once('partials/widgets.php');
            HeaderWidget('Search Table');
            require_once('partials/conditionalCSS.php');
        ?>
    </head>

    <body>

        <!-- navbar -->
        <?php Navbar(); ?>

        <!-- Page title below navbar -->
        <?php TitleBannerRender(databaseName: $databaseSearch->getCleanName(), recordNumber: $result->getFoundSetCount()); ?>

        <?php $tableData = new TableData($result, $databaseSearch) ?>

        <!-- main body with table and its widgets -->
        <div class="container-fluid flex-grow-1">

            <!-- menu buttons for render table -->
            <div class="d-flex flex-wrap flex-row justify-content-evenly align-items-center py-2 px-1 p-md-4 gap-4">
                <!-- review search parameters -->
                <button type="button" data-bs-toggle="collapse" data-bs-target="#advancedSearchDiv"
                        class="btn btn-outline-secondary conditional-outline-background"
                        >Review Search Parameters</button>

                <!-- edit table columns -->
                <button type="button" data-bs-toggle="collapse" data-bs-target="#tableColumnFilterDiv"
                        class="btn btn-outline-secondary conditional-outline-background">Hide/Show Columns</button>

                <!-- enable/disable images -->
                <div class="btn-group">
                    <span class="input-group-text">View Images:</span>
                    <input type="radio" name="viewImages" id="noImages"
                           class="btn-check radio-conditional-background" value="no" checked>
                    <label for="noImages" class="btn btn-outline-secondary">No</label>

                    <input type="radio" name="viewImages" id="yesImage"
                           class="btn-check radio-conditional-background" value="yes">
                    <label for="yesImage" class="btn btn-outline-secondary">Yes</label>
                </div>

                <!-- Download Data Button Trigger Modal-->
                <button type="button" class="btn btn-outline-secondary conditional-outline-background" data-bs-toggle="modal" data-bs-target="#exampleModal">
                    Download Data
                </button>

                <!-- Modal -->
                <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="staticBackdropLabel">Download Notice</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                You are only able to download the <strong>first 1000 records</strong> of your search.
                                If you would like to download a larger amount of data, please contact the database curator or modify your search.
                                <br><br>
                                Data are provided "as is" for informational purposes only. The curators of the collections make no guarantees, either express or implied, about the completeness, accuracy, or currency of the information, or its suitability for any particular purpose. Although each curator strives to provide the most accurate and up-to-date information possible, outdated names, mistaken identifications and erroneous localities can and do occur. Users of the data are urged to verify all data by direct examination of specimens and associated records and to alert the curators (https://beatymuseum.ubc.ca/connect/contact/) of any suspect data records.
                                When you use these data, please acknowledge the Beaty Biodiversity Museum and the specific collection you extracted data from, and when possible, please use the citation below.
                                <br><br>
                                UBC Beaty Biodiversity Museum Collections, <?php echo date("Y"); ?>. Data obtained from the collection <?php echo $databaseSearch->getCleanName(); ?>. https://bridge.botany.ubc.ca/herbarium/index.php. Accessed <?php echo date("Y-m-d"); ?>.
                                <br><br>
                                <strong>Do you accept the these terms?</strong>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <form action="#" method="post">
                                    <input class="btn conditional-background" type="submit" name="downloadData" value="Accept and Download" />
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- start a new search -->
                <a href="search.php?Database=<?=DATABASE?>" type="button"
                   class="btn btn-outline-secondary conditional-outline-background">New Search</a>
            </div>

            <div id="menuCollapsable">
                <!-- edit advanced search collapsible -->
                <div class="collapse w-100" data-bs-parent="#menuCollapsable" id="advancedSearchDiv">
                    <div class="d-flex justify-content-around align-items-center px-5 py-3">
                        <form action="render.php" method="get" id="submit-form">
                            <!-- hidden text field containing the database name -->
                            <label>
                                <input type="text" hidden id="Database" name="Database" value=<?php echo htmlspecialchars(DATABASE); ?>>
                            </label>
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
                                $databaseFields = $databaseSearch->getSearchLayout()->getFields();
                                // if database name is algae, remove Class from the fields
                                if (DATABASE == "algae") {
                                    unset($databaseFields['Class']);
                                }
                                foreach ($databaseFields as $fieldName => $field) : ?>

                                    <div class="px-3 py-2 py-md-1 flex-fill responsive-columns-3">
                                        <!-- field name and input -->
                                        <div class="input-group">
                                            <a data-bs-toggle="collapse" href="#collapsable<?=$count?>" role="button">
                                                <label class="input-group-text conditional-background-light"
                                                       for="field-<?php echo htmlspecialchars($fieldName)?>">
                                                    <?php echo htmlspecialchars(Specimen::FormatFieldName($fieldName)) ?>
                                                </label>
                                            </a>
                                            <?php
                                            # Try to get a list of options, if error (aka none available) then no datalist
                                            try {
                                                $fieldValues = $field->getValueList();
                                            } catch (FileMakerException $e) { /* Do nothing */ }

                                            if (isset($fieldValues)) : ?>
                                                <input class="form-control" list="datalistOptions"
                                                       placeholder="Type to search" id="field-<?php echo htmlspecialchars($fieldName)?>"
                                                       name="<?php echo htmlspecialchars($fieldName)?>">
                                                <datalist id="datalistOptions">
                                                    <?php foreach ($fieldValues as $fieldValue): ?>
                                                        <option value="<?=$fieldValue?>"></option>
                                                    <?php endforeach; ?>
                                                </datalist>
                                            <?php else:
                                                $value = array_key_exists($fieldName, $_GET) ? $_GET[$fieldName] : null;
                                                ?>
                                                <input class="form-control" type="<?php echo $field->getResult() ?>"
                                                       id="field-<?php echo htmlspecialchars($fieldName)?>"
                                                       name="<?php echo htmlspecialchars($fieldName)?>"
                                                       value="<?=$value?>">
                                            <?php endif; ?>
                                        </div>
                                        <!-- field information -->
                                        <div class="collapse" id="collapsable<?=$count?>">
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
                                    <!-- we go with checked if operator does not exist since that's the default -->
                                    <input type="radio" class="btn-check radio-conditional-background"
                                           name="operator" id="and" value="and"
                                        <?php echo array_key_exists('operator', $_GET) ? $_GET['operator'] == 'and' ? 'checked' : '' : 'checked' ?>
                                           autocomplete="off">
                                    <label class="btn btn-outline-secondary" for="and"> AND </label>

                                    <input type="radio" class="btn-check radio-conditional-background"
                                           name="operator" id="or" value="or"
                                        <?php echo array_key_exists('operator', $_GET) ? $_GET['operator'] == 'or' ? 'checked' : '' : '' ?>
                                           autocomplete="off">
                                    <label class="btn btn-outline-secondary" for="or"> OR </label>
                                </div>

                                <!-- only with image select, tooltip to explain why disabled -->
                                <div class="form-check form-switch" <?php if (!$databaseSearch->hasImages()) echo 'data-bs-toggle="tooltip" title="No images available"' ?>>
                                    <label class="form-check-label">
                                        <input type="checkbox" class="form-check-input checkbox-conditional-background"
                                            <?php if (!$databaseSearch->hasImages()) echo 'disabled' ?>
                                            <?php echo array_key_exists('hasImage', $_GET) ? $_GET['hasImage'] == 'on' ? 'checked' : '' : '' ?>
                                               name="hasImage">
                                        Only show records that contain an image
                                    </label>
                                </div>

                                <!-- submit button -->
                                <div class="form-group">
                                    <button type="submit" onclick="submitForm()" class="btn btn-outline-primary conditional-background"> Advanced Search </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- edit table columns -->
                <div class="collapse w-100" data-bs-parent="#menuCollapsable" id="tableColumnFilterDiv">
                    <div class="d-flex flex-wrap flex-row justify-content-around px-5 py-3 gap-3">
                        <?php foreach ($tableData->getUsefulFields() as $fieldName): ?>
                            <div class="btn-group me-auto">
                                <span class="input-group-text"><?=htmlspecialchars(Specimen::FormatFieldName($fieldName))?></span>
                                <input type="radio" name="view<?=htmlspecialchars(Specimen::FormatFieldName($fieldName))?>" id="show<?=htmlspecialchars(Specimen::FormatFieldName($fieldName))?>"
                                       class="btn-check radio-conditional-background" value="show" checked>
                                <label for="show<?=htmlspecialchars(Specimen::FormatFieldName($fieldName))?>" class="btn btn-outline-secondary">Show</label>

                                <input type="radio" name="view<?=htmlspecialchars(Specimen::FormatFieldName($fieldName))?>" id="hide<?=htmlspecialchars(Specimen::FormatFieldName($fieldName))?>"
                                       class="btn-check radio-conditional-background" value="Hide">
                                <label for="hide<?=htmlspecialchars(Specimen::FormatFieldName($fieldName))?>" class="btn btn-outline-secondary">Hide</label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- render table with data -->
            <div class="table-responsive">
                <!-- id used to js -->
                <table class="table table-hover table-striped" id="table">
                    <thead>
                        <tr>
                            <?php foreach ($tableData->getTableHeads(page: $_GET['Page'] ?? 1,
                                requestUri: $_SERVER['REQUEST_URI'], sort: ($_GET['Sort'] ?? ''),
                                sortOrder: ($_GET['SortOrder'] ?? '') ) as $id => $href): ?>
                                <th scope="col" id="<?= $id ?>" class="text-center">
                                    <a href="<?= $href ?>" class="table-col-header conditional-text-color" role="button">
                                        <!-- field name -->
                                        <!-- if id equal to sort order, make italic -->
                                        <?php $semNumPrinted = false ?>
                                        <?php if (isset($_GET['Sort']) and $id == $_GET['Sort'] and $_GET['SortOrder'] == 'Ascend'): ?>
                                            <b><?= $id ?>▲</b>
                                        <?php endif; ?>
                                        <?php if (isset($_GET['Sort']) and $id == $_GET['Sort'] and $_GET['SortOrder'] == 'Descend'): ?>
                                            <b><?= $id ?>▼</b>
                                        <?php endif; ?>
                                        <?php if (isset($_GET['Sort']) and 'SEM Number' == $id and $_GET['Sort'] == 'SEM #' and $_GET['SortOrder'] == 'Ascend'): ?>
                                            <b><?= $id ?>▲</b>
                                            <?php $semNumPrinted = true ?>
                                        <?php endif; ?>
                                        <?php if (isset($_GET['Sort']) and 'SEM Number' == $id and $_GET['Sort'] == 'SEM #' and $_GET['SortOrder'] == 'Descend'): ?>
                                            <b><?= $id ?>▼</b>
                                            <?php $semNumPrinted = true ?>
                                        <?php endif; ?>
                                        <?php if (!isset($_GET['Sort']) or $_GET['Sort'] != $id and !$semNumPrinted): ?>
                                            <b><?= $id ?>↑↓</b>
                                        <?php endif; ?>
                                    </a>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php try {
                            foreach ($tableData->getTableRows() as $tableRow): ?>
                                <tr class="conditional-hover-background">
                                    <!-- row header with link to go to specimen page -->
                                    <th scope="row" class="text-nowrap">
                                        <a href="details.php?Database=<?= $tableRow->getUrl() ?>" role="button"
                                           class="conditional-text-color">
                                            <b><?= $tableRow->getId() ?></b>

                                            <?php if ($tableRow->isHasImage()): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                     fill="currentColor" class="bi bi-card-image " viewBox="0 0 16 16">
                                                    <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                                                    <path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13zm13 1a.5.5 0 0 1 .5.5v6l-3.775-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12v.54A.505.505 0 0 1 1 12.5v-9a.5.5 0 0 1 .5-.5h13z"/>
                                                </svg>
                                            <?php endif; ?>
                                        </a>
                                    </th>

                                    <!-- all other row columns with data -->
                                    <?php foreach ($tableRow->getFields() as $field): ?>
                                        <td class="text-center" id="data"><?= $field ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach;
                        } catch (FileMakerException $e) {
                            $_SESSION['error'] = $e->getMessage();
                            header('Location: error.php');
                            exit;
                        } ?>
                    </tbody>
                </table>
            </div>

            <!-- table controller -->
            <div class="p-3">
                <?php TableControllerWidget($maxResponses, $result); ?>
            </div>
        </div>

        <?php FooterWidget(imgSrc: 'public/images/beatyLogo.png'); ?>

        <!-- scripts -->
        <script type="text/javascript" src="public/js/advanced-search.js"></script>
        <script type="text/javascript" src="public/js/hide-show-columns.js"></script>
    </body>
</html>