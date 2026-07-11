{{--
	Topbar customer switcher - renders ONLY for a multi-customer account (2+
	cus_users_customers pivot links). Lists every linked customer in approval
	order; picking one POSTs to customer.switch, which stores the choice in the
	session and the reload re-renders every page block with that customer's
	data. The active row is marked with a check and is not re-submittable.
	Single-customer and unlinked accounts render nothing. Data comes from
	PortalController::commonContext() ($linkedCustomers + $activeCustomerId);
	the popover mirrors the bell popover Alpine pattern.
--}}
@php
	$linkedCustomers  = $linkedCustomers ?? [];
	$activeCustomerId = (int) ($activeCustomerId ?? 0);
	$activeCustomer   = null;
	foreach ($linkedCustomers as $c) {
		if ((int) $c['id'] === $activeCustomerId) {
			$activeCustomer = $c;
			break;
		}
	}
@endphp

@if (count($linkedCustomers) > 1 && $activeCustomer !== null)
	<div class="p-cswitch" x-data="{ cs: false }">
		<button type="button" class="p-cswitch-btn" :class="{ 'is-on': cs }" @click="cs = !cs"
		        aria-label="Ügyfél váltása" title="Ügyfél váltása">
			<i data-lucide="users" class="lucide-sm"></i>
			<span class="p-cswitch-label">
				<span class="name">{{ $activeCustomer['name'] }}</span>
				@if ($activeCustomer['number'] !== '')
					<span class="num">{{ $activeCustomer['number'] }}</span>
				@endif
			</span>
			<i data-lucide="chevron-down" class="lucide-xs"></i>
		</button>

		<div class="p-cswitch-scrim" x-show="cs" x-cloak @click="cs = false"></div>
		<div class="p-cswitch-pop" x-show="cs" x-cloak x-transition>
			<div class="head"><strong>Ügyfél váltása</strong></div>
			<div class="list">
				@foreach ($linkedCustomers as $c)
					@if ((int) $c['id'] === $activeCustomerId)
						<div class="p-cswitch-item is-active">
							<span class="txt">
								<span class="n">{{ $c['name'] }}</span>
								@if ($c['number'] !== '')
									<span class="d">{{ $c['number'] }}</span>
								@endif
							</span>
							<i data-lucide="check" class="lucide-xs"></i>
						</div>
					@else
						<form method="POST" action="{{ route('customer.switch') }}">
							@csrf
							<input type="hidden" name="customer_id" value="{{ (int) $c['id'] }}">
							<button type="submit" class="p-cswitch-item">
								<span class="txt">
									<span class="n">{{ $c['name'] }}</span>
									@if ($c['number'] !== '')
										<span class="d">{{ $c['number'] }}</span>
									@endif
								</span>
							</button>
						</form>
					@endif
				@endforeach
			</div>
		</div>
	</div>
@endif
