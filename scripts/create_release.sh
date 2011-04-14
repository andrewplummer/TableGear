#!/bin/bash


rm releases/TableGear$1-MooTools.zip
rm releases/TableGear$1-jQuery.zip

cd lib/

cat example.php jquery.php > index.php
zip ../releases/TableGear-jQuery-$1.zip index.php javascripts/tablegear-jquery.js include/* stylesheets/* images/*

cat example.php mootools.php > index.php
zip ../releases/TableGear-MooTools-$1.zip index.php javascripts/tablegear-mootools.js include/* stylesheets/* images/*

rm index.php
