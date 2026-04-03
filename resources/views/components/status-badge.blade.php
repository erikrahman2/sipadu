@php
  $colors = [
    'DRAFT'                    => 'bg-gray-100 text-gray-600',
    'SUBMITTED'                => 'bg-blue-100 text-blue-700',
    'OCR_PROCESSED'            => 'bg-indigo-100 text-indigo-700',
    'PA_REVIEW'                => 'bg-yellow-100 text-yellow-700',
    'DISDUKCAPIL_VALIDATION'   => 'bg-orange-100 text-orange-700',
    'COMPLETED'                => 'bg-green-100 text-green-700',
    'ARCHIVED'                 => 'bg-purple-100 text-purple-700',
    'REJECTED'                 => 'bg-red-100 text-red-700',
    // Document statuses
    'PENDING'                  => 'bg-gray-100 text-gray-500',
    'PROCESSING'               => 'bg-blue-100 text-blue-600',
    'PROCESSED'                => 'bg-green-100 text-green-600',
    'VALIDATED'                => 'bg-teal-100 text-teal-700',
    'active'                   => 'bg-green-100 text-green-700',
    'inactive'                 => 'bg-gray-100 text-gray-500',
    'suspended'                => 'bg-red-100 text-red-600',
  ];
  $sizeClass = match($size ?? 'sm') {
    'xs' => 'text-[10px] px-1.5 py-0.5',
    'lg' => 'text-sm px-3 py-1.5',
    default => 'text-xs px-2 py-0.5',
  };
  
  // Get label with role-based conditional
  $defaultLabel = config("workflow.states.{$status}") ?? $status;
  $label = $defaultLabel;
  
  // Role-based display for OCR_PROCESSED
  if ($status === 'OCR_PROCESSED' && auth()->check()) {
    if (auth()->user()->hasRole('pa_assistant')) {
      $label = 'Submitted';
    } elseif (auth()->user()->hasRole('pa_management')) {
      $label = 'Diproses';
    }
  }
@endphp
<span class="inline-flex items-center rounded-full font-medium {{ $sizeClass }} {{ $colors[$status] ?? 'bg-gray-100 text-gray-600' }}">
  {{ $label }}
</span>
