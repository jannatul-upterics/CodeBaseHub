<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers with server-side search and pagination.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $portfolio = $request->query('portfolio', '');

        $query = Customer::query()->withCount('websites');

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($portfolio === 'has_sites') {
            $query->has('websites');
        } elseif ($portfolio === 'no_sites') {
            $query->doesntHave('websites');
        }

        $customers = $query->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('customers.index', compact('customers', 'search', 'portfolio'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create(): View
    {
        return view('customers.create');
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password'] ?? Str::random(12));

        $customer = Customer::create($data);

        return redirect()
            ->route('customers.index')
            ->with('success', "Customer '{$customer->name}' created successfully.");
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer): View
    {
        $customer->load(['websites.primaryDomain']);

        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $customer->update($data);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Customer '{$customer->name}' updated successfully.");
    }

    /**
     * Remove the specified customer from storage with safe relationship handling.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        $name = $customer->name;
        $sitesCount = $customer->websites()->count();

        $customer->delete();

        $message = "Customer '{$name}' deleted successfully.";
        if ($sitesCount > 0) {
            $message .= " Associated {$sitesCount} website(s) have been safely unassigned.";
        }

        return redirect()
            ->route('customers.index')
            ->with('success', $message);
    }
}
