<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->get('admin/dashboard', 'Admin\Dashboard::index');
$routes->post('admin/profile/update', 'Admin\Dashboard::updateProfile');
$routes->post(
    'admin/users/create',
    'Admin\User::create'
);
$routes->post(
    'admin/barbers/status/(:num)',
    'Admin\User::updateStatus/$1'
);

$routes->get('admin/schedule/get', 'Admin\Dashboard::getSchedule');
$routes->post('admin/schedule/update', 'Admin\Dashboard::updateSchedule');

$routes->get('admin/booking/today-details', 'Admin\Dashboard::getTodayBookingDetails');
$routes->post('admin/booking/update-status', 'Admin\Dashboard::updateBookingStatus');
$routes->post('admin/dashboard/updateStatus', 'Admin\Dashboard::updateStatus');
$routes->post('admin/dashboard/saveWalkIn', 'Admin\Dashboard::saveWalkIn');

// Rute untuk Booking Pelanggan (Public)
$routes->get('booking', 'Booking::index');
$routes->post('booking/store', 'Booking::store');
$routes->post('admin/dashboard/approveBooking', 'Admin\Dashboard::approveBooking');
$routes->post('admin/dashboard/rejectBooking', 'Admin\Dashboard::rejectBooking');
$routes->post('admin/dashboard/updateStatus', 'Admin\Dashboard::updateStatus');
$routes->post('admin/dashboard/saveService', 'Admin\Dashboard::saveService');
$routes->post('admin/dashboard/deleteService', 'Admin\Dashboard::deleteService');

$routes->post('admin/dashboard/saveSettings', 'Admin\Dashboard::saveSettings');


