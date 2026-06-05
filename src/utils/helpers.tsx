import React from 'react';
import * as Lucide from 'lucide-react';

// Reusable alert component
export const Alert: React.FC<{
  message: string;
  type?: 'success' | 'warning' | 'danger' | 'info';
  onDismiss?: () => void;
}> = ({ message, type = 'success', onDismiss }) => {
  const bgMap = {
    success: 'bg-[#DCFCE7] text-[#15803D] border-[#BBF7D0]',
    warning: 'bg-[#FEF3C7] text-[#B45309] border-[#FDE68A]',
    danger: 'bg-[#FEE2E2] text-[#B91C1C] border-[#FCA5A5]',
    info: 'bg-[#DBEAFE] text-[#1D4ED8] border-[#BFDBFE]'
  };

  const IconMap = {
    success: Lucide.CheckCircle2,
    warning: Lucide.AlertTriangle,
    danger: Lucide.XCircle,
    info: Lucide.Info
  };

  const IconComp = IconMap[type];

  return (
    <div className={`p-4 border rounded-lg flex items-center justify-between shadow-sm transition-all animate-fadeIn ${bgMap[type]}`}>
      <div className="flex items-center gap-3">
        <IconComp className="w-5 h-5 flex-shrink-0" />
        <span className="font-body-base text-body-base font-medium">{message}</span>
      </div>
      {onDismiss && (
        <button
          onClick={onDismiss}
          className="text-current opacity-70 hover:opacity-100 p-1 rounded-full hover:bg-black/5"
        >
          <Lucide.X className="w-4 h-4" />
        </button>
      )}
    </div>
  );
};

// Reusable badge component
export const Badge: React.FC<{
  text: string;
  type?: 'success' | 'warning' | 'danger' | 'info';
}> = ({ text, type = 'info' }) => {
  const styles = {
    success: 'bg-[#DCFCE7] text-[#166534] border-[#BBF7D0]',
    warning: 'bg-[#FEF3C7] text-[#92400E] border-[#FDE68A]',
    danger: 'bg-[#FEE2E2] text-[#991B1B] border-[#FCA5A5]',
    info: 'bg-[#E0F2FE] text-[#0369A1] border-[#BAE6FD]'
  };

  return (
    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider border ${styles[type]}`}>
      {text}
    </span>
  );
};

// Helper for Grade Letter estimation based on score percentages
export const gradeFromMarks = (marks: number, max: number = 100): string => {
  const percentage = (marks / max) * 100;
  if (percentage >= 90) return 'A';
  if (percentage >= 80) return 'B+';
  if (percentage >= 70) return 'B';
  if (percentage >= 60) return 'C';
  if (percentage >= 50) return 'D';
  return 'F';
};

// Attendance safety warn tags
export const attendanceStatus = (percentage: number): 'safe' | 'warning' | 'critical' => {
  if (percentage >= 85) return 'safe';
  if (percentage >= 75) return 'warning';
  return 'critical';
};

// Reusable Stat Card Component
export const StatCard: React.FC<{
  title: string;
  value: string | number;
  icon: keyof typeof Lucide;
  color?: 'blue' | 'green' | 'amber' | 'red' | 'navy';
  subtext?: string;
  trend?: {
    value: string;
    isPositive: boolean;
  };
}> = ({ title, value, icon, color = 'blue', subtext, trend }) => {
  const IconComponent = Lucide[icon] as React.ComponentType<{ className?: string }>;

  const colorStyles = {
    blue: {
      border: 'border-l-4 border-l-[#1D4ED8]',
      iconBg: 'bg-[#DBEAFE] text-[#1D4ED8]'
    },
    green: {
      border: 'border-l-4 border-l-[#10B981]',
      iconBg: 'bg-[#D1FAE5] text-[#10B981]'
    },
    amber: {
      border: 'border-l-4 border-l-[#F59E0B]',
      iconBg: 'bg-[#FEF3C7] text-[#F59E0B]'
    },
    red: {
      border: 'border-l-4 border-l-[#EF4444]',
      iconBg: 'bg-[#FEE2E2] text-[#EF4444]'
    },
    navy: {
      border: 'border-l-4 border-l-[#0F172A]',
      iconBg: 'bg-[#E2E8F0] text-[#0F172A]'
    }
  };

  return (
    <div className={`bg-white rounded-[10px] border border-[#E2E8F0] p-6 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group ${colorStyles[color].border}`}>
      <div className="flex justify-between items-start mb-4">
        <h3 className="font-label-md text-label-md text-[#64748B] uppercase tracking-wider">{title}</h3>
        <div className={`p-2 rounded-lg transition-colors ${colorStyles[color].iconBg}`}>
          {IconComponent && <IconComponent className="w-5 h-5" />}
        </div>
      </div>
      <div className="flex items-baseline gap-2">
        <span className="font-display-lg text-display-lg text-[#0F172A] font-bold">{value}</span>
        {trend && (
          <span className={`text-xs font-bold ${trend.isPositive ? 'text-[#10B981]' : 'text-[#EF4444]'}`}>
            {trend.value}
          </span>
        )}
      </div>
      {subtext && (
        <div className="text-body-sm text-[#64748B] mt-2 font-body-sm">
          {subtext}
        </div>
      )}
    </div>
  );
};

// Reusable Paginate layout component
export const Paginate: React.FC<{
  total: number;
  perPage: number;
  current: number;
  onChange: (page: number) => void;
}> = ({ total, perPage, current, onChange }) => {
  const totalPages = Math.ceil(total / perPage);
  if (totalPages <= 1) return null;

  const pages = [];
  for (let i = 1; i <= totalPages; i++) {
    pages.push(i);
  }

  return (
    <div className="flex items-center justify-between border-t border-[#E2E8F0] px-4 py-3 sm:px-6 mt-4">
      <div className="flex flex-1 justify-between sm:hidden">
        <button
          onClick={() => onChange(Math.max(1, current - 1))}
          disabled={current === 1}
          className="relative inline-flex items-center rounded-md border border-[#E2E8F0] bg-white px-4 py-2 text-sm font-medium text-[#64748B] hover:bg-gray-50 disabled:opacity-50"
        >
          Previous
        </button>
        <button
          onClick={() => onChange(Math.min(totalPages, current + 1))}
          disabled={current === totalPages}
          className="relative ml-3 inline-flex items-center rounded-md border border-[#E2E8F0] bg-white px-4 py-2 text-sm font-medium text-[#64748B] hover:bg-gray-50 disabled:opacity-50"
        >
          Next
        </button>
      </div>
      <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
        <div>
          <p className="text-sm text-[#64748B]">
            Showing <span className="font-medium">{Math.min(total, (current - 1) * perPage + 1)}</span> to{' '}
            <span className="font-medium">{Math.min(total, current * perPage)}</span> of{' '}
            <span className="font-medium">{total}</span> results
          </p>
        </div>
        <div>
          <nav className="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
            <button
              onClick={() => onChange(Math.max(1, current - 1))}
              disabled={current === 1}
              className="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-[#E2E8F0] hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 cursor-pointer"
            >
              <Lucide.ChevronLeft className="h-5 w-5" aria-hidden="true" />
            </button>
            {pages.map((p) => (
              <button
                key={p}
                onClick={() => onChange(p)}
                aria-current={current === p ? 'page' : undefined}
                className={`relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20 cursor-pointer ${
                  current === p
                    ? 'z-10 bg-[#1D4ED8] text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1D4ED8]'
                    : 'text-[#0F172A] ring-1 ring-inset ring-[#E2E8F0] hover:bg-gray-50 focus:outline-offset-0'
                }`}
              >
                {p}
              </button>
            ))}
            <button
              onClick={() => onChange(Math.min(totalPages, current + 1))}
              disabled={current === totalPages}
              className="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-[#E2E8F0] hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 cursor-pointer"
            >
              <Lucide.ChevronRight className="h-5 w-5" aria-hidden="true" />
            </button>
          </nav>
        </div>
      </div>
    </div>
  );
};
