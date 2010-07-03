#!/bin/bash


rm releases/TableGear$1-MooTools.zip
rm releases/TableGear$1-jQuery.zip

cd lib/

zip ../releases/TableGear$1-MooTools.zip javascripts/TableGear$1-MooTools.js index.php include/* stylesheets/* images/*
zip ../releases/TableGear$1-jQuery.zip javascripts/TableGear$1-jQuery.js index.php include/* stylesheets/* images/*

