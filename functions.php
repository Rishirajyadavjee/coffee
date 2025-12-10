<?php
session_start();

// check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// check if user is admin
function isAdmin() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
}
