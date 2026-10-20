<?php

namespace App\Livewire\Dashboard;

use App\Models\PlatformSetting;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.dashboard')]
class Deposit extends Component
{
    use WithFileUploads;

    private const CRYPTOCURRENCIES = [
        'BTC' => 'Bitcoin (BTC)',
        'ETH' => 'Ethereum (ETH)',
        'USDT-TRC20' => 'USDT (TRC20)',
        'USDT-ERC20' => 'USDT (ERC20)',
        'BNB' => 'BNB (BSC)',
        'SOL' => 'Solana (SOL)',
        'XRP' => 'XRP',
        'DOGE' => 'Dogecoin (DOGE)',
        'LTC' => 'Litecoin (LTC)',
        'TRX' => 'Tron (TRX)',
    ];

    public string $cryptocurrency = '';

    public $amount = '';

    public $screenshot;

    public function submitDeposit(): void
    {
        $this->validate([
            'cryptocurrency' => ['required', 'string', Rule::in(array_keys(self::CRYPTOCURRENCIES))],
            'amount' => ['required', 'numeric', 'min:1'],
            'screenshot' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:5120'],
        ]);

        $path = $this->screenshot->store('deposit-proof', 'public');

        auth()->user()->deposits()->create([
            'cryptocurrency' => $this->cryptocurrency,
            'amount' => (int) round(((float) $this->amount) * 100),
            'proof_of_payment_path' => $path,
            'status' => 'pending',
        ]);

        $this->reset('cryptocurrency', 'amount', 'screenshot');

        session()->flash('toast', 'Deposit request submitted successfully');
        $this->redirect(route('dashboard.wallet'), navigate: true);
    }

    public function render()
    {
        return view('livewire.dashboard.deposit', [
            'cryptocurrencies' => self::CRYPTOCURRENCIES,
            'depositAddress' => PlatformSetting::current()->deposit_wallet_addresses[$this->cryptocurrency] ?? null,
        ]);
    }
}
