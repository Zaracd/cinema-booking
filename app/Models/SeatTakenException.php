<?php
namespace App\Models;

/** Thrown when a seat was booked by someone else first. */
class SeatTakenException extends \RuntimeException {}
