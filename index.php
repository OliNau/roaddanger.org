<?php

require_once 'initialize.php';

global $VERSION;
global $database;
global $user;

$uri = urldecode($_SERVER['REQUEST_URI']);
if      (str_starts_with($uri, '/last_changed'))                    $pageType = PageType::lastChanged;
else if (str_starts_with($uri, '/decorrespondent'))                 $pageType = PageType::deCorrespondent;
else if (str_starts_with($uri, '/map'))                             $pageType = PageType::map;
else if (str_starts_with($uri, '/moderations'))                     $pageType = PageType::moderations;
else if (str_starts_with($uri, '/mosaic'))                          $pageType = PageType::mosaic;
else if (str_starts_with($uri, '/child_deaths'))                    $pageType = PageType::childDeaths;
else if (str_starts_with($uri, '/statistics/general'))              $pageType = PageType::statisticsGeneral;
else if (str_starts_with($uri, '/statistics/counterparty'))         $pageType = PageType::statisticsCrashPartners;
else if (str_starts_with($uri, '/statistics/transportation_modes')) $pageType = PageType::statisticsTransportationModes;
else if (str_starts_with($uri, '/statistics'))                      $pageType = PageType::statisticsGeneral;
else if (str_starts_with($uri, '/export'))                          $pageType = PageType::export;
else $pageType = PageType::recent;

$addSearchBar   = false;
$showButtonAdd  = false;
$head = "<script src='/js/main.js?v=$VERSION'></script>";
if ($pageType === PageType::statisticsCrashPartners){
  $head .= "<script src='/scripts/d3.v5.js?v=$VERSION'></script><script src='/js/d3CirclePlot.js?v=$VERSION'></script>";
}

// Open streetmap
//<link rel='stylesheet' href='https://unpkg.com/leaflet@1.3.1/dist/leaflet.css' integrity='sha512-Rksm5RenBEKSKFjgI3a41vrjkw4EVPlJ3+OiI65vTjIdo9brlAacEuKOiQ5OFh7cOI1bkDwLqdLw3Zg0cRJAAQ==' crossorigin=''/>
//<script src='https://unpkg.com/leaflet@1.3.1/dist/leaflet.js' integrity='sha512-/Nsx9X4HebavoBvEBuyp3I7od5tA0UzAxs+j83KgC8PU0kgB4XiK4Lfe4y4cgBtaRJQEIFCW+oC506aPT2L1zw==' crossorigin=''></script>

// Maptiler using mapbox
//  <script src="https://cdn.maptiler.com/ol/v5.3.0/ol.js"></script>
//  <script src="https://cdn.maptiler.com/ol-mapbox-style/v4.3.1/olms.js"></script>
//  <link rel="stylesheet" href="https://cdn.maptiler.com/ol/v5.3.0/ol.css">

// Mapbox tiles
//<link href='https://api.tiles.mapbox.com/mapbox-gl-js/v0.53.1/mapbox-gl.css' rel='stylesheet'>
//<script src='https://api.tiles.mapbox.com/mapbox-gl-js/v0.53.1/mapbox-gl.js'></script>

if (pageWithMap($pageType)) {
  $mapbox_js  = MAPBOX_GL_JS;
  $mapbox_css = MAPBOX_GL_CSS;
  $head .= <<<HTML
<script src="$mapbox_js"></script>
<link href="$mapbox_css" type="text/css" rel="stylesheet">
HTML;
}

if (pageWithEditMap($pageType)) {
  $mapbox_geocoder_js  = MAPBOX_GEOCODER_JS;
  $mapbox_geocoder_css = MAPBOX_GEOCODER_CSS;
  $head .= <<<HTML
<script src="$mapbox_geocoder_js"></script>
<link href="$mapbox_geocoder_css" type="text/css" rel="stylesheet">
HTML;
}


if ($pageType === PageType::statisticsGeneral) {
  $texts = translateArray(['Statistics', 'General']);

  $mainHTML = <<<HTML
<div id="pageMain">
  <div class="pageInner pageInnerScroll">    
    <div class="pageSubTitle">{$texts['Statistics']} - {$texts['General']}</div>
    
    <div class="panelTableOverflow">
       <table id="tableStatistics" class="dataTable"></table>
      <div id="spinnerLoad"><img src="/images/spinner.svg" alt="spinner"></div>
    </div>
    
  </div>
</div>
HTML;
} else if ($pageType === PageType::childDeaths) {

  $showButtonAdd = true;
  $texts = translateArray(['Child_deaths', 'Injury', 'Dead_(adjective)', 'Injured', 'Help_improve_data_accuracy']);
  $intro = $user->translateLongText('child_deaths_info');

  $mainHTML = <<<HTML

<div id="pageMain">

  <div class="pageSubTitle"><img src="/images/child.svg" style="height: 20px; position: relative; top: 2px;"> {$texts['Child_deaths']}</div>
  <div style="display: flex; flex-direction: column; align-items: center">
    <div style="text-align: left;">
      <div class="smallFont" style="text-decoration: underline; cursor: pointer" onclick="togglePageInfo();">{$texts['Help_improve_data_accuracy']}</div>
    </div>
  </div>
  
  <div id="pageInfo" style="display: none; max-width: 600px; margin: 10px 0;">
  $intro
</div>

  <div class="searchBar" style="display: flex; padding-bottom: 0;">

    <div class="toolbarItem">
      <span id="filterChildDead" class="menuButton bgDeadBlack" data-tippy-content="{$texts['Injury']}: {$texts['Dead_(adjective)']}" onclick="selectFilterChildDeaths();"></span>      
      <span id="filterChildInjured" class="menuButton bgInjuredBlack" data-tippy-content="{$texts['Injury']}: {$texts['Injured']}" onclick="selectFilterChildDeaths();"></span>      
    </div>
    
  </div>

  <div class="panelTableOverflow">
    <table class="dataTable">
      <tbody id="dataTableBody"></tbody>
    </table>
    <div id="spinnerLoad"><img src="/images/spinner.svg"></div>
  </div>
  
</div>
  
HTML;

} else if ($pageType === PageType::map) {
  $showButtonAdd = true;
  $addSearchBar  = true;

  $mainHTML = <<<HTML
  <div id="mapMain"></div>
HTML;

} else if ($pageType === PageType::mosaic) {
  $showButtonAdd = true;
  $addSearchBar  = true;
  $mainHTML = <<<HTML
<div id="pageMain">
  <div id="cards"></div>
  <div id="spinnerLoad"><img src="/images/spinner.svg"></div>
</div>
HTML;
} else if ($pageType === PageType::statisticsCrashPartners) {

  $texts = translateArray(['Counterparty_in_crashes', 'Always', 'days', 'the_correspondent_week', 'Custom_period',
    'Help_improve_data_accuracy', 'Child', 'Injury', 'Injured', 'Dead_(adjective)', 'Search_text_hint',
    'Search', 'Filter']);
  $intoText = $user->translateLongText('counter_party_info');

  $htmlSearchCountry = getSearchCountryHtml('selectFilterStats');
  $htmlSearchPeriod  = getSearchPeriodHtml('selectFilterStats');

  $mainHTML = <<<HTML
<div id="pageMain">

  <div style="width: 100%; max-width: 700px;">

  <div style="display: flex; flex-direction: column; align-items: center">
    <div style="text-align: left;">
      <div class="pageSubTitleFont">{$texts['Counterparty_in_crashes']}</div>
      <div class="smallFont" style="text-decoration: underline; cursor: pointer" onclick="togglePageInfo();">{$texts['Help_improve_data_accuracy']}</div>
    </div>
  </div>
  
  <div id="pageInfo" style="display: none; margin: 10px 0;">
  $intoText
</div>

  <div id="statistics">
  
    <div class="searchBar" style="display: flex;">

      <div class="toolbarItem">
        <span id="filterStatsDead" class="menuButton bgDeadBlack" data-tippy-content="{$texts['Injury']}: {$texts['Dead_(adjective)']}" onclick="selectFilterStats();"></span>      
        <span id="filterStatsInjured" class="menuButton bgInjuredBlack" data-tippy-content="{$texts['Injury']}: {$texts['Injured']}" onclick="selectFilterStats();"></span>      
        <span id="filterStatsChild" class="menuButton bgChild" data-tippy-content="{$texts['Child']}" onclick="selectFilterStats();"></span>      
      </div>
      
      <div class="toolbarItem">$htmlSearchCountry</div>
      $htmlSearchPeriod
      
      <div class="toolbarItem">
        <input id="searchText" class="searchInput textInputWidth"  type="search" data-tippy-content="{$texts['Search_text_hint']}" placeholder="{$texts['Search']}" onkeyup="startStatsSearchKey(event);" autocomplete="off">  
      </div>

      <div class="toolbarItem">
        <div class="button buttonMobileSmall" style="margin-left: 0;" onclick="loadStatistics(event)">{$texts['Search']}</div>
      </div>

    </div>

    <div id="graphPartners" style="position: relative;"></div>
   
  </div>
  
  <div id="spinnerLoad"><img src="/images/spinner.svg"></div>
  </div>
</div>
HTML;

} else if ($pageType === PageType::statisticsTransportationModes) {

  $texts = translateArray(['Statistics', 'Transportation_modes', 'Transportation_mode', 'Child', 'Country',
    'Intoxicated', 'Drive_on_or_fleeing', 'Dead_(adjective)', 'Injured', 'Unharmed', 'Unknown', 'Search_text_hint',
    'Search', 'Filter']);

  $htmlSearchCountry = getSearchCountryHtml();
  $htmlSearchPeriod  = getSearchPeriodHtml();

  $mainHTML = <<<HTML
<div class="pageInner">
  <div class="pageSubTitle">{$texts['Statistics']} - {$texts['Transportation_modes']}<span class="iconTooltip" data-tippy-content="Dit zijn de cijfers over de ongelukken tot nog toe in de database."></span></div>
  
  <div id="statistics">
  
    <div class="searchBar" style="display: flex;">
      <div class="toolbarItem">
        <span id="filterStatsChild" class="menuButton bgChild" data-tippy-content="{$texts['Child']}" onclick="selectFilterStats();"></span>      
      </div>

      <div class="toolbarItem">$htmlSearchCountry</div>
      $htmlSearchPeriod

      <div class="toolbarItem">
        <input id="searchText" class="searchInput textInputWidth"  type="search" data-tippy-content="{$texts['Search_text_hint']}" placeholder="{$texts['Search']}" onkeyup="startStatsSearchKey(event);" autocomplete="off">  
      </div>

      <div class="toolbarItem">
        <div class="button buttonMobileSmall" style="margin-left: 0;" onclick="loadStatistics(event)">{$texts['Filter']}</div>
      </div>
      
    </div>

    <table class="dataTable">
      <thead>
        <tr>
          <th style="text-align: left;">{$texts['Transportation_mode']}</th>
          <th><div class="flexRow" style="justify-content: flex-end;"><div class="iconSmall bgDead" data-tippy-content="{$texts['Dead_(adjective)']}"></div> <div class="hideOnMobile">{$texts['Dead_(adjective)']}</div></div></th>
          <th><div class="flexRow" style="justify-content: flex-end;"><div class="iconSmall bgInjured" data-tippy-content="{$texts['Injured']}"></div> <div  class="hideOnMobile">{$texts['Injured']}</div></div></th>
          <th><div class="flexRow" style="justify-content: flex-end;"><div class="iconSmall bgUnharmed" data-tippy-content="{$texts['Unharmed']}"></div> <div  class="hideOnMobile">{$texts['Unharmed']}</div></div></th>
          <th><div class="flexRow" style="justify-content: flex-end;"><div class="iconSmall bgUnknown" data-tippy-content="{$texts['Unknown']}"></div> <div  class="hideOnMobile">{$texts['Unknown']}</div></div></th>
          <th style="text-align: right;"><div class="iconSmall bgChild" data-tippy-content="{$texts['Child']}"></div></th>
          <th style="text-align: right;"><div class="iconSmall bgAlcohol" data-tippy-content="{$texts['Intoxicated']}"></div></th>
          <th style="text-align: right;"><div class="iconSmall bgHitRun" data-tippy-content="{$texts['Drive_on_or_fleeing']}"></div></th>
        </tr>
      </thead>  
      <tbody id="tableStatsBody">
        
      </tbody>
    </table>  
  </div>
  <div id="spinnerLoad"><img src="/images/spinner.svg"></div>
</div>
HTML;

} else if ($pageType === PageType::export) {
  $mainHTML = <<<HTML
<div id="main" class="pageInner">
  <div class="pageSubTitle">Export</div>
  <div id="export">

    <div class="sectionTitle">Download</div>

    <div>All crash data can be exported in gzip JSON format. The download is refreshed every 24 hours.
    </div> 
    
    <div class="buttonBar" style="justify-content: center; margin-bottom: 30px;">
      <button class="button" style="margin-left: 0; height: auto;" onclick="downloadData();">Download data<br>in gzip JSON formaat</button>
    </div>  
    <div id="spinnerLoad"><img src="/images/spinner.svg"></div>
    
    <div class="sectionTitle">Data specification</div>
    
    <div class="tableHeader">Persons > transportationmode</div>
    
    <table class="dataTable" style="width: auto; margin: 0 0 20px 0;">
      <thead>
      <tr><th>id</th><th>name</th></tr>
      </thead>
      <tbody id="tbodyTransportationMode"></tbody>
    </table>        

    <div class="tableHeader">Persons > health</div>
    <table class="dataTable" style="width: auto; margin: 0 0 20px 0;">
      <thead>
      <tr><th>id</th><th>name</th></tr>
      </thead>
      <tbody id="tbodyHealth"></tbody>
    </table>
            
  </div>
</div>
HTML;
} else {
  $addSearchBar  = true;
  $showButtonAdd = true;
  $messageHTML   = translateLongText('website_info');

  $title = '';
  switch ($pageType){
    case PageType::lastChanged:          $title = translate('Last_modified_crashes'); break;
    case PageType::deCorrespondent: $title = translate('The_correspondent_week') . '<br>14-20 jan. 2019'; break;
    case PageType::moderations:     $title = translate('Moderations'); break;
    case PageType::recent:          $title = translate('Recent_crashes'); break;
  }

  $introText = "<div id='pageSubTitle' class='pageSubTitle'>$title</div>";

  if (isset($messageHTML) && in_array($pageType, [PageType::recent, PageType::lastChanged, PageType::deCorrespondent, PageType::crash])) {
    $introText .= "<div class='sectionIntro'>$messageHTML</div>";
  }

  $mainHTML = <<<HTML
<div id="pageMain">
  <div class="pageInner">
    $introText
    <div id="cards"></div>
    <div id="spinnerLoad"><img src="/images/spinner.svg"></div>
  </div>
</div>
HTML;

  $head .= '<script src="/scripts/mark.es6.js"></script>';
}

$html =
  getHTMLBeginMain('', $head, 'initMain', $addSearchBar, $showButtonAdd) .
  $mainHTML .
  getHTMLEnd();

echo $html;