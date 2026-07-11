<?php

namespace App\Http\Controllers;

use App\Models\ContractRequest;
use App\Models\CustomerUser;
use App\Support\CustomerMail;
use App\Support\Recaptcha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * RegisterController - multi-step self-registration.
 *
 * The 3-step wizard is client-side (Alpine, resources/js/auth-forms.js); the
 * final step POSTs everything here. We create the cus_users account (unverified)
 * + a PENDING cus_contract_requests row (staff approve the contract link later
 * in rgadmin), send the e-mail verification link, and route the visitor to the
 * "check your inbox" notice. No automatic customer matching happens here.
 */
class RegisterController extends Controller
{
	public function show(): View
	{
		return view('auth.register');
	}

	/**
	 * Validate + create the account, the pending contract request and send the
	 * verification e-mail.
	 *
	 * @example  POST /regisztracio  ->  {redirect: '/regisztracio/megerosites'}
	 */
	public function store(Request $request): JsonResponse
	{
		$data = $request->validate([
			'lastName'  => 'required|string|max:100',
			'firstName' => 'required|string|max:100',
			'email'     => ['required', 'email', 'max:200', Rule::unique('cus_users', 'email')],
			'phone'     => 'required|string|max:50',
			'birth_date' => 'nullable|date|before:today',
			'contract_number' => 'nullable|string|max:50',
			'password' => ['required', 'string', 'min:10', 'confirmed', 'regex:/[A-Z]/', 'regex:/[0-9]/'],
			'accept'   => 'accepted',
		], $this->messages(), $this->attributes());

		// Identification (contract number + birth date) is OPTIONAL: the customer
		// can register without it and supply it later in the portal. A request row
		// is created regardless (it lists as "Adatokra vár" in rgadmin until the
		// contract number + birth date are given).

		// reCAPTCHA (registration form only).
		if (! Recaptcha::verify($request)) {
			throw ValidationException::withMessages([
				'g-recaptcha-response' => 'Kérjük, igazolja, hogy nem robot (reCAPTCHA).',
			]);
		}

		$user = CustomerUser::create([
			'name'       => trim($data['lastName'] . ' ' . $data['firstName']),
			'email'      => $data['email'],
			'phone'      => $data['phone'],
			// The DOB lands on the ACCOUNT too (Profil shows it, the add-contract
			// form prefills from it), not only on the contract request (E3-L3).
			'birth_date' => $data['birth_date'] ?? null,
			'password'   => $data['password'], // hashed by the model cast
			'status'     => 1,
		]);

		ContractRequest::create([
			'cus_users_id'    => $user->id,
			'contract_number' => $data['contract_number'] ?? null,
			'birth_date'      => $data['birth_date'],
			'status'          => 'pending',
		]);

		$this->sendVerification($user);

		$request->session()->put('verify_email', $user->email);

		return response()->json(['redirect' => route('register.verify.notice')]);
	}

	/**
	 * Send the e-mail verification link (24h signed URL).
	 */
	private function sendVerification(CustomerUser $user): void
	{
		$url = URL::temporarySignedRoute('verification.verify', now()->addDay(), [
			'id'   => $user->id,
			'hash' => sha1($user->email),
		]);

		CustomerMail::send('customer_verify_email', $user->email, [
			'{neve}'       => $user->name,
			'{verify_url}' => $url,
		]);
	}

	/**
	 * Hungarian validation messages.
	 *
	 * @return array<string, string>
	 */
	private function messages(): array
	{
		return [
			'lastName.required'  => 'Kérjük, adja meg vezetéknevét.',
			'firstName.required' => 'Kérjük, adja meg keresztnevét.',
			'email.required'     => 'Kérjük, adja meg e-mail címét.',
			'email.email'        => 'Kérjük, adjon meg egy érvényes e-mail címet (pl. nev@example.hu).',
			'email.unique'       => 'Ezzel az e-mail címmel már regisztráltak. Lépjen be, vagy állítson be új jelszót.',
			'phone.required'     => 'Kérjük, adja meg telefonszámát.',
			'birth_date.required' => 'Kérjük, adja meg születési dátumát.',
			'birth_date.date'    => 'Kérjük, érvényes születési dátumot adjon meg.',
			'birth_date.before'  => 'Kérjük, érvényes születési dátumot adjon meg.',
			'password.required'  => 'Kérjük, adjon meg egy jelszót.',
			'password.min'       => 'A jelszónak legalább 10 karakter hosszúnak kell lennie.',
			'password.confirmed' => 'A két megadott jelszó nem egyezik.',
			'password.regex'     => 'A jelszónak tartalmaznia kell nagybetűt és számot is.',
			'accept.accepted'    => 'A regisztráció lezárásához el kell fogadnia az ÁSZF-et és az Adatvédelmi Tájékoztatót.',
		];
	}

	/**
	 * Hungarian field names for any unmapped validation messages.
	 *
	 * @return array<string, string>
	 */
	private function attributes(): array
	{
		return [
			'lastName'        => 'vezetéknév',
			'firstName'       => 'keresztnév',
			'email'           => 'e-mail cím',
			'phone'           => 'telefonszám',
			'birth_date'      => 'születési dátum',
			'contract_number' => 'szerződésszám',
			'password'        => 'jelszó',
		];
	}
}
