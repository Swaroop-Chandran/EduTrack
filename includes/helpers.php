<?php
// HTML Layout Helpers and Utilities

/**
 * Helper to compute grade letter from marks percentage.
 */
function gradeFromMarks(float $marks, float $max = 100.0): string {
    $percentage = ($marks / $max) * 100;
    if ($percentage >= 90) return 'A';
    if ($percentage >= 80) return 'B+';
    if ($percentage >= 70) return 'B';
    if ($percentage >= 60) return 'C';
    if ($percentage >= 50) return 'D';
    return 'F';
}

/**
 * Attendance rate safety category estimation.
 */
function attendanceStatus(float $percentage): string {
    if ($percentage >= 85) return 'safe';
    if ($percentage >= 75) return 'warning';
    return 'critical';
}

/**
 * Renders a dismissible Tailwind Alert box matching original Alert component classes.
 */
function renderAlert(string $message, string $type = 'success'): string {
    $bgMap = [
        'success' => 'bg-[#DCFCE7] text-[#15803D] border-[#BBF7D0]',
        'warning' => 'bg-[#FEF3C7] text-[#B45309] border-[#FDE68A]',
        'danger'  => 'bg-[#FEE2E2] text-[#B91C1C] border-[#FCA5A5]',
        'info'    => 'bg-[#DBEAFE] text-[#1D4ED8] border-[#BFDBFE]'
    ];

    $iconMap = [
        'success' => 'check-circle-2',
        'warning' => 'alert-triangle',
        'danger'  => 'x-circle',
        'info'    => 'info'
    ];

    $bgClass = $bgMap[$type] ?? $bgMap['success'];
    $icon = $iconMap[$type] ?? $iconMap['success'];

    return '
    <div id="alert_box" class="p-4 border rounded-lg flex items-center justify-between shadow-sm transition-all animate-fadeIn ' . $bgClass . '">
        <div class="flex items-center gap-3">
            <i data-lucide="' . $icon . '" class="w-5 h-5 flex-shrink-0"></i>
            <span class="font-medium text-sm">' . htmlspecialchars($message) . '</span>
        </div>
        <button onclick="document.getElementById(\'alert_box\').style.display=\'none\';" class="text-current opacity-70 hover:opacity-100 p-1 rounded-full hover:bg-black/5">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>';
}

/**
 * Renders a small badge tag matching original Badge component design.
 */
function renderBadge(string $text, string $type = 'info'): string {
    $styles = [
        'success' => 'bg-[#DCFCE7] text-[#166534] border-[#BBF7D0]',
        'warning' => 'bg-[#FEF3C7] text-[#92400E] border-[#FDE68A]',
        'danger'  => 'bg-[#FEE2E2] text-[#991B1B] border-[#FCA5A5]',
        'info'    => 'bg-[#E0F2FE] text-[#0369A1] border-[#BAE6FD]'
    ];

    $badgeStyle = $styles[$type] ?? $styles['info'];
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider border ' . $badgeStyle . '">' . htmlspecialchars($text) . '</span>';
}

/**
 * Renders a statistical KPI card with border-left accent bars matching StatCard component.
 */
function renderStatCard(string $title, $value, string $icon, string $color = 'blue', string $subtext = '', array $trend = null): string {
    $colorStyles = [
        'blue' => [
            'border' => 'border-l-4 border-l-[#1D4ED8]',
            'iconBg' => 'bg-[#DBEAFE] text-[#1D4ED8]'
        ],
        'green' => [
            'border' => 'border-l-4 border-l-[#10B981]',
            'iconBg' => 'bg-[#D1FAE5] text-[#10B981]'
        ],
        'amber' => [
            'border' => 'border-l-4 border-l-[#F59E0B]',
            'iconBg' => 'bg-[#FEF3C7] text-[#F59E0B]'
        ],
        'red' => [
            'border' => 'border-l-4 border-l-[#EF4444]',
            'iconBg' => 'bg-[#FEE2E2] text-[#EF4444]'
        ],
        'navy' => [
            'border' => 'border-l-4 border-l-[#0F172A]',
            'iconBg' => 'bg-[#E2E8F0] text-[#0F172A]'
        ]
    ];

    $style = $colorStyles[$color] ?? $colorStyles['blue'];

    $trendHtml = '';
    if ($trend) {
        $trendColor = $trend['isPositive'] ? 'text-[#10B981]' : 'text-[#EF4444]';
        $trendHtml = '<span class="text-xs font-bold ' . $trendColor . '">' . htmlspecialchars($trend['value']) . '</span>';
    }

    $subtextHtml = '';
    if ($subtext !== '') {
        $subtextHtml = '<div class="text-xs text-[#64748B] mt-2">' . htmlspecialchars($subtext) . '</div>';
    }

    return '
    <div class="bg-white rounded-[10px] border border-[#E2E8F0] p-6 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group ' . $style['border'] . '">
        <div class="flex justify-between items-start mb-4">
            <h3 class="text-xs font-bold text-[#64748B] uppercase tracking-wider">' . htmlspecialchars($title) . '</h3>
            <div class="p-2 rounded-lg transition-colors ' . $style['iconBg'] . '">
                <i data-lucide="' . strtolower($icon) . '" class="w-5 h-5"></i>
            </div>
        </div>
        <div class="flex items-baseline gap-2">
            <span class="text-3xl text-[#0F172A] font-bold tracking-tight">' . htmlspecialchars($value) . '</span>
            ' . $trendHtml . '
        </div>
        ' . $subtextHtml . '
    </div>';
}

/**
 * Renders pagination controls identical to original Paginate component.
 */
function renderPaginate(int $total, int $perPage, int $current, string $tabName): string {
    $totalPages = (int)ceil($total / $perPage);
    if ($totalPages <= 1) {
        return '';
    }

    $start = max(1, ($current - 1) * $perPage + 1);
    $end = min($total, $current * $perPage);

    $prevPage = max(1, $current - 1);
    $nextPage = min($totalPages, $current + 1);

    // Generate page buttons
    $pagesHtml = '';
    for ($i = 1; $i <= $totalPages; $i++) {
        $activeClass = ($current === $i) 
            ? 'z-10 bg-[#1D4ED8] text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1D4ED8]' 
            : 'text-[#0F172A] ring-1 ring-inset ring-[#E2E8F0] hover:bg-gray-50';
        $pagesHtml .= '<a href="?tab=' . urlencode($tabName) . '&page=' . $i . '" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20 ' . $activeClass . '">' . $i . '</a>';
    }

    return '
    <div class="flex items-center justify-between border-t border-[#E2E8F0] px-4 py-3 sm:px-6 mt-4">
        <!-- Mobile Layout -->
        <div class="flex flex-1 justify-between sm:hidden">
            <a href="?tab=' . urlencode($tabName) . '&page=' . $prevPage . '" class="relative inline-flex items-center rounded-md border border-[#E2E8F0] bg-white px-4 py-2 text-sm font-medium text-[#64748B] hover:bg-gray-50' . ($current === 1 ? ' opacity-50 pointer-events-none' : '') . '">Previous</a>
            <a href="?tab=' . urlencode($tabName) . '&page=' . $nextPage . '" class="relative ml-3 inline-flex items-center rounded-md border border-[#E2E8F0] bg-white px-4 py-2 text-sm font-medium text-[#64748B] hover:bg-gray-50' . ($current === $totalPages ? ' opacity-50 pointer-events-none' : '') . '">Next</a>
        </div>
        <!-- Desktop Layout -->
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-[#64748B]">
                    Showing <span class="font-medium">' . $start . '</span> to <span class="font-medium">' . $end . '</span> of <span class="font-medium">' . $total . '</span> results
                </p>
            </div>
            <div>
                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                    <a href="?tab=' . urlencode($tabName) . '&page=' . $prevPage . '" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-[#E2E8F0] hover:bg-gray-50 focus:z-20' . ($current === 1 ? ' opacity-50 pointer-events-none' : '') . '">
                        <i data-lucide="chevron-left" class="h-5 w-5"></i>
                    </a>
                    ' . $pagesHtml . '
                    <a href="?tab=' . urlencode($tabName) . '&page=' . $nextPage . '" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-[#E2E8F0] hover:bg-gray-50 focus:z-20' . ($current === $totalPages ? ' opacity-50 pointer-events-none' : '') . '">
                        <i data-lucide="chevron-right" class="h-5 w-5"></i>
                    </a>
                </nav>
            </div>
        </div>
    </div>';
}
