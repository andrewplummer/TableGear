#!/bin/bash


rm releases/TableGear$1-MooTools.zip
rm releases/TableGear$1-jQuery.zip

cd lib/

cat example.php jquery.php > index.php
zip ../releases/TableGear$1-jQuery.zip index.php javascripts/TableGear$1-jQuery.js include/* stylesheets/* images/*

cat example.php mootools.php > index.php
zip ../releases/TableGear$1-MooTools.zip index.php javascripts/TableGear$1-MooTools.js include/* stylesheets/* images/*

rm index.php
