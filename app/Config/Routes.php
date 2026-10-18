<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->get('register', 'Home::registerPage');
$routes->post('register', 'Home::register');
$routes->post('login', 'Home::login');
$routes->get('account/password', 'Home::passwordPage');
$routes->post('account/password', 'Home::changePassword');
$routes->get('customer', 'Home::customer');
$routes->post('customer/requests', 'Home::createRequest');
$routes->post('customer/quotes', 'Home::requestQuote');
$routes->get('admin', 'Home::admin');
$routes->post('admin/quotes/(:num)', 'Home::updateQuote/$1');
$routes->post('admin/installations', 'Home::createInstallation');
$routes->post('admin/installations/(:num)', 'Home::updateInstallation/$1');
$routes->post('admin/requests/(:num)', 'Home::updateRequest/$1');
$routes->post('admin/users/(:num)/status', 'Home::updateStatus/$1');
$routes->post('admin/staff', 'Home::createEmployee');
$routes->post('admin/staff/(:num)/status', 'Home::updateEmployeeStatus/$1');
$routes->get('terms', 'Home::terms');
$routes->get('privacy', 'Home::privacy');
$routes->post('logout', 'Home::logout');
