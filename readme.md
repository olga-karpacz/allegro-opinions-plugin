# Allegro opinions plugin
Allegro opinions plugin 

## Table of contents
* [General info](#general-info)
* [Technologies](#technologies)
* [Setup](#setup)

## General info
Plugin for Allegro API integration - displaying customers opinions. 

![alt text](https://github.com/olga-karpacz/allegro-opinions-plugin/blob/main/screenshot.png)

Using the shortcode [opinie_allegro] you can add a block with up to 6 latest customer reviews along with their rating and data. The reviews are presented in Polish with translated months, because Allegro is most often used by Polish customers.

Developed based on Allegro API documentation (https://developer.allegro.pl/documentation)

Plugin is currently intended for use in a Sandbox test environment. All URL's for production environment are commented in the code.
	
## Technologies
Project is created with:
* PHP - version 8
* used WordPress hooks and actions
* tested with WordPress 6.7.1
	
## Setup
To run this project, install it as new WordPress plugin.
You need your CLIENT ID and CLIENT SECRET from Allegro App - more info here: https://developer.allegro.pl/tutorials/uwierzytelnianie-i-autoryzacja-zlq9e75GdIR#rejestracja-aplikacji
