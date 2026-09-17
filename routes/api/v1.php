<?php

use App\Http\Controllers\Api\V1\AboutUsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookNowController;
use App\Http\Controllers\Api\V1\ContactMessageController;
use App\Http\Controllers\Api\V1\DailyTourBookingController;
use App\Http\Controllers\Api\V1\DailyTourCategoryController;
use App\Http\Controllers\Api\V1\DailyTourController;
use App\Http\Controllers\Api\V1\FlightBookingController;
use App\Http\Controllers\Api\V1\FlightController;
use App\Http\Controllers\Api\V1\HotelBookingController;
use App\Http\Controllers\Api\V1\HolidayPackageBookingController;
use App\Http\Controllers\Api\V1\HolidayPackageController;
use App\Http\Controllers\Api\V1\HotelController;
use App\Http\Controllers\Api\V1\NewsletterController;
use App\Http\Controllers\Api\V1\PopularDestinationBookingController;
use App\Http\Controllers\Api\V1\PopularDestinationCategoryController;
use App\Http\Controllers\Api\V1\PopularDestinationController;
use App\Http\Controllers\Api\V1\TransferBookingController;
use App\Http\Controllers\Api\V1\TransferController;
use App\Http\Controllers\Api\V1\VisaApplicationController;
use App\Http\Controllers\Api\V1\VisaDestinationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes  →  /api/v1
|
| Public: GET listings + POST client forms (book / contact / newsletter)
| Admin:  POST/PATCH/DELETE content + GET/PATCH/DELETE bookings
|         require Sanctum Bearer token + Spatie permission
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return response()->json([
        'name' => 'Al-Gewar API',
        'version' => 'v1',
        'status' => 'ok',
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

/*
| Airport Transfers
*/
Route::prefix('airport-transfers')->group(function () {
    Route::get('/', [TransferController::class, 'index']);

    Route::middleware(['auth:sanctum', 'permission:airport-transfers.manage'])->group(function () {
        Route::post('/', [TransferController::class, 'store']);
        Route::patch('/', [TransferController::class, 'update']);
        Route::delete('/', [TransferController::class, 'destroy']);
    });

    Route::prefix('book')->group(function () {
        Route::post('/', [TransferBookingController::class, 'store']);

        Route::middleware(['auth:sanctum', 'permission:bookings.view|bookings.manage'])->group(function () {
            Route::get('/', [TransferBookingController::class, 'index']);
        });

        Route::middleware(['auth:sanctum', 'permission:bookings.manage'])->group(function () {
            Route::patch('/', [TransferBookingController::class, 'update']);
            Route::delete('/', [TransferBookingController::class, 'destroy']);
        });
    });
});

/*
| About Us
*/
Route::prefix('about-us')->group(function () {
    Route::get('/', [AboutUsController::class, 'show']);

    Route::middleware(['auth:sanctum', 'permission:about-us.manage'])->group(function () {
        Route::post('/', [AboutUsController::class, 'store']);
        Route::patch('/', [AboutUsController::class, 'update']);
    });
});

/*
| Contact — public form submit only
*/
Route::prefix('contact-messages')->group(function () {
    Route::post('/', [ContactMessageController::class, 'store']);

    Route::middleware(['auth:sanctum', 'permission:contact-messages.view'])->group(function () {
        Route::get('/', [ContactMessageController::class, 'index']);
    });

    Route::middleware(['auth:sanctum', 'permission:contact-messages.manage'])->group(function () {
        Route::patch('/', [ContactMessageController::class, 'update']);
        Route::delete('/', [ContactMessageController::class, 'destroy']);
    });
});

/*
| Visa Services
*/
Route::prefix('visa-services')->group(function () {
    Route::get('/', [VisaDestinationController::class, 'index']);

    Route::middleware(['auth:sanctum', 'permission:visa-services.manage'])->group(function () {
        Route::post('/', [VisaDestinationController::class, 'store']);
        Route::patch('/', [VisaDestinationController::class, 'update']);
        // Route::post('/update', [VisaDestinationController::class, 'update']);
        Route::delete('/', [VisaDestinationController::class, 'destroy']);
    });

    Route::prefix('apply')->group(function () {
        Route::post('/', [VisaApplicationController::class, 'store']);

        Route::middleware(['auth:sanctum', 'permission:bookings.view|bookings.manage'])->group(function () {
            Route::get('/', [VisaApplicationController::class, 'index']);
        });

        Route::middleware(['auth:sanctum', 'permission:bookings.manage'])->group(function () {
            Route::patch('/', [VisaApplicationController::class, 'update']);
            Route::delete('/', [VisaApplicationController::class, 'destroy']);
        });
    });
});

/*
| Flights
*/
Route::prefix('flights')->group(function () {
    Route::get('/', [FlightController::class, 'index']);

    Route::middleware(['auth:sanctum', 'permission:flights.manage'])->group(function () {
        Route::post('/', [FlightController::class, 'store']);
        Route::patch('/', [FlightController::class, 'update']);
        Route::delete('/', [FlightController::class, 'destroy']);
    });

    Route::prefix('book')->group(function () {
        Route::post('/', [FlightBookingController::class, 'store']);

        Route::middleware(['auth:sanctum', 'permission:bookings.view|bookings.manage'])->group(function () {
            Route::get('/', [FlightBookingController::class, 'index']);
        });

        Route::middleware(['auth:sanctum', 'permission:bookings.manage'])->group(function () {
            Route::patch('/', [FlightBookingController::class, 'update']);
            Route::delete('/', [FlightBookingController::class, 'destroy']);
        });
    });
});

/*
| Hotels
*/
Route::prefix('hotels')->group(function () {
    Route::get('/', [HotelController::class, 'index']);

    Route::middleware(['auth:sanctum', 'permission:hotels.manage'])->group(function () {
        Route::post('/', [HotelController::class, 'store']);
        Route::patch('/', [HotelController::class, 'update']);
        Route::delete('/', [HotelController::class, 'destroy']);
    });

    Route::prefix('book')->group(function () {
        Route::post('/', [HotelBookingController::class, 'store']);

        Route::middleware(['auth:sanctum', 'permission:bookings.view|bookings.manage'])->group(function () {
            Route::get('/', [HotelBookingController::class, 'index']);
        });

        Route::middleware(['auth:sanctum', 'permission:bookings.manage'])->group(function () {
            Route::patch('/', [HotelBookingController::class, 'update']);
            Route::delete('/', [HotelBookingController::class, 'destroy']);
        });
    });
});

/*
| Daily Tours — list returns categories; tours live under a category
*/
Route::prefix('daily-tours')->group(function () {
    Route::get('/', [DailyTourController::class, 'index']);

    Route::prefix('categories')->group(function () {
        Route::get('/', [DailyTourCategoryController::class, 'index']);

        Route::middleware(['auth:sanctum', 'permission:daily-tours.manage'])->group(function () {
            Route::post('/', [DailyTourCategoryController::class, 'store']);
            Route::patch('/', [DailyTourCategoryController::class, 'update']);
            Route::delete('/', [DailyTourCategoryController::class, 'destroy']);
        });
    });

    Route::middleware(['auth:sanctum', 'permission:daily-tours.manage'])->group(function () {
        Route::post('/', [DailyTourController::class, 'store']);
        Route::patch('/', [DailyTourController::class, 'update']);
        Route::delete('/', [DailyTourController::class, 'destroy']);
    });

    Route::prefix('book')->group(function () {
        Route::post('/', [DailyTourBookingController::class, 'store']);

        Route::middleware(['auth:sanctum', 'permission:bookings.view|bookings.manage'])->group(function () {
            Route::get('/', [DailyTourBookingController::class, 'index']);
        });

        Route::middleware(['auth:sanctum', 'permission:bookings.manage'])->group(function () {
            Route::patch('/', [DailyTourBookingController::class, 'update']);
            Route::delete('/', [DailyTourBookingController::class, 'destroy']);
        });
    });
});

/*
| Book Now
*/
Route::prefix('book-now')->group(function () {
    Route::get('/', [BookNowController::class, 'options']);

    foreach (['flights', 'hotels', 'tours', 'transfers', 'visa'] as $tab) {
        Route::prefix($tab)->group(function () use ($tab) {
            Route::post('/', [BookNowController::class, 'store'])->defaults('tab', $tab);

            Route::middleware(['auth:sanctum', 'permission:book-now.view|book-now.manage'])->group(function () use ($tab) {
                Route::get('/', [BookNowController::class, 'index'])->defaults('tab', $tab);
            });

            Route::middleware(['auth:sanctum', 'permission:book-now.manage'])->group(function () use ($tab) {
                Route::patch('/', [BookNowController::class, 'update'])->defaults('tab', $tab);
                Route::delete('/', [BookNowController::class, 'destroy'])->defaults('tab', $tab);
            });
        });
    }
});

/*
| Popular Destinations — list returns categories; destinations live under a category
*/
Route::prefix('popular-destinations')->group(function () {
    Route::get('/', [PopularDestinationController::class, 'index']);

    Route::prefix('categories')->group(function () {
        Route::get('/', [PopularDestinationCategoryController::class, 'index']);

        Route::middleware(['auth:sanctum', 'permission:popular-destinations.manage'])->group(function () {
            Route::post('/', [PopularDestinationCategoryController::class, 'store']);
            Route::patch('/', [PopularDestinationCategoryController::class, 'update']);
            Route::delete('/', [PopularDestinationCategoryController::class, 'destroy']);
        });
    });

    Route::middleware(['auth:sanctum', 'permission:popular-destinations.manage'])->group(function () {
        Route::post('/', [PopularDestinationController::class, 'store']);
        Route::patch('/', [PopularDestinationController::class, 'update']);
        Route::delete('/', [PopularDestinationController::class, 'destroy']);
    });

    Route::prefix('book')->group(function () {
        Route::post('/', [PopularDestinationBookingController::class, 'store']);

        Route::middleware(['auth:sanctum', 'permission:bookings.view|bookings.manage'])->group(function () {
            Route::get('/', [PopularDestinationBookingController::class, 'index']);
        });

        Route::middleware(['auth:sanctum', 'permission:bookings.manage'])->group(function () {
            Route::patch('/', [PopularDestinationBookingController::class, 'update']);
            Route::delete('/', [PopularDestinationBookingController::class, 'destroy']);
        });
    });
});

/*
| Holiday Packages
*/
Route::prefix('holiday-packages')->group(function () {
    Route::get('/', [HolidayPackageController::class, 'index']);

    Route::middleware(['auth:sanctum', 'permission:holiday-packages.manage'])->group(function () {
        Route::post('/', [HolidayPackageController::class, 'store']);
        Route::patch('/', [HolidayPackageController::class, 'update']);
        Route::delete('/', [HolidayPackageController::class, 'destroy']);
    });

    Route::prefix('book')->group(function () {
        Route::post('/', [HolidayPackageBookingController::class, 'store']);

        Route::middleware(['auth:sanctum', 'permission:bookings.view|bookings.manage'])->group(function () {
            Route::get('/', [HolidayPackageBookingController::class, 'index']);
        });

        Route::middleware(['auth:sanctum', 'permission:bookings.manage'])->group(function () {
            Route::patch('/', [HolidayPackageBookingController::class, 'update']);
            Route::delete('/', [HolidayPackageBookingController::class, 'destroy']);
        });
    });
});

/*
| Newsletter — public subscribe only
*/
Route::prefix('newsletter')->group(function () {
    Route::post('/', [NewsletterController::class, 'store']);

    Route::middleware(['auth:sanctum', 'permission:newsletter.view|newsletter.manage'])->group(function () {
        Route::get('/', [NewsletterController::class, 'index']);
    });

    Route::middleware(['auth:sanctum', 'permission:newsletter.manage'])->group(function () {
        Route::patch('/', [NewsletterController::class, 'update']);
        Route::delete('/', [NewsletterController::class, 'destroy']);
    });
});
