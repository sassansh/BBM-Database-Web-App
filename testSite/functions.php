<?php 
// require_once ('db.php');

// $fm = new FileMaker($FM_FILE, $FM_HOST, $FM_USER, $FM_PASS);

function replaceURIElement($URI, $element, $input) {
  if (isset($_GET[$element])) {
    $elementLeft = strpos($URI, $element);
    $elementRight = strpos($URI, '&', $elementLeft);
    $stringRight = "";
    if ($elementRight) {
      $stringRight = substr($URI, $elementRight, strlen($URI));
    }
    return substr($URI, 0, $elementLeft) 
    . 
    $element . '=' . $input . $stringRight;
  } else {
    return $URI . '&' . $element . '=' . $input;
  }
}

function replaceSpace($element) {
  return str_replace(" ", "+", $element);
}

function mapField($field) {
    switch( strtolower($field)) {
      case 'accession no':
      case 'catalognumber':
      case 'id':
        return 'Accession Number';
      case 'sem #':
        return 'SEM Number';
      case 'specificepithet':  
        return 'Species';
      case 'sub sp.':
        return 'Subspecies';
      case 'infraspecificepithet':
        return 'Infraspecies';
      case 'taxonrank': 
        return 'Taxon Rank';
      case 'provincestate':
      case 'stateprovince':
      case 'prov/st';
        return 'Province or State';
      case 'location 1':
      case 'location':
        return 'Locality';
      case 'verbatimelevation':
        return 'Elevation';
      case 'verbatimdepth':
      case 'depth below water':
        return 'Depth';
      case 'geo_longdecimal':
      case 'decimallongitude':
      case 'longitudedecimal':
        return 'Longitude';
      case 'geo_latdecimal':
      case 'decimallatitude':
      case 'latitudedecimal':
        return 'Latitude';
      case 'date collected':
      case 'collection date 1':
      case 'verbatimeventdate':
      case 'eventdate':
        return 'Collection Date';
      case 'year 1':
        return 'Year';
      case 'month 1':
        return 'Month';
      case 'day 1':
        return 'Day';
      case 'identifiedby':
        return 'Identified By';
      case 'typestatus':
        return 'Type Status';
      case 'comments':
      case 'occurrenceremarks':
      case 'fieldnotes':
        return 'Field Notes';
      case 'recordnumber':
        return 'Collection Number';
      case 'previousidentifications':
        return 'Previous Identifications';
      case 'det by':
        return 'Determined By';
      case 'mushroomobserver':
        return 'Mushroom Observer';
      case 'citations':
      case 'associatedreferences':
        return 'Associated References';
      case 'associatedsequences':
        return 'Associated Sequences';
      case 'reproductivecondition':
        return 'Reproductive Condition';
      case 'organismremark':
        return 'Organism Remark';
      case 'vernacularname':
        return 'Vernacular Name';
      case 'recordedby':
      case 'collected by':
        return 'Collector';
      case 'photofilename': 
      case 'iifrno':
      case 'imaged':
        return 'Has Image';
      default:
        return ucwords($field);
      }
  }
  
  function formatField($field) {
    $colonPosition = strrpos($field, ":");
    if ($colonPosition) {
      $field = substr($field, $colonPosition + 1);
    }
    return mapField($field);
  }

  function getGenusPage($record) {
    $order = $record->getField('Order');
    $family = $record->getField('Family');
    $genusPage = 'http://www.zoology.ubc.ca/entomology/main/'.$order.'/'.$family.'/';
    return $genusPage;
  }

  function getGenusSpecies($record) {
    $genus = $record->getField('Genus');
    $species = $record->getField('Species');
    $genusSpecies = $genus . ' ' . $species ;
    return $genusSpecies;
  }
?>
