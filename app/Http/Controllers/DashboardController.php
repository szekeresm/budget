<?php

namespace App\Http\Controllers;

use App\Helper;
use App\Repositories\TagRepository;
use Illuminate\Http\Request;
use App\Models\Earning;
use App\Models\Recurring;
use App\Models\Spending;
use App\Repositories\DashboardRepository;
use Auth;
use DB;

class DashboardController extends Controller
{
    private $dashboardRepository;
    private $tagRepository;

    public function __construct(DashboardRepository $dashboardRepository, TagRepository $tagRepository)
    {
        $this->dashboardRepository = $dashboardRepository;
        $this->tagRepository = $tagRepository;
    }

    public function __invoke()
    {
        $space_id = session('space')->id;
        $currentYear = date('Y');
        $currentMonth = date('m');
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);

        $mostExpensiveTags = $this->tagRepository->getMostExpensiveTags($space_id, 3, $currentYear, $currentMonth);

        return view('dashboard', [
            'month' => date('n'),

            'balance' => $this->dashboardRepository->getBalance($currentYear, $currentMonth),
            'recurrings' => $this->dashboardRepository->getRecurrings($currentYear, $currentMonth),
            'leftToSpend' => $this->dashboardRepository->getLeftToSpend($currentYear, $currentMonth),

            'totalSpent' => $this->dashboardRepository->getTotalAmountSpent($currentYear, $currentMonth),
            'mostExpensiveTags' => $mostExpensiveTags,

            'daysInMonth' => $daysInMonth,
            'dailyBalance' => $this->dashboardRepository->getDailyBalance($currentYear, $currentMonth)
        ]);
    }
}
