<?php

session_set_cookie_params([
  'lifetime' => 0,  // Sessionen dör när webbläsaren stängs
  'httponly' => true, // Förhindrar att sessionen kan nås via JavaScript
  'samesite' => 'Strict', // Förhindrar att sessionen skickas med cross-site requests
  'secure' => true // Sessionen kräver en säker anslutning
]);

session_start();
