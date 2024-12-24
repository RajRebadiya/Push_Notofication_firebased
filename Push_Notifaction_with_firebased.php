Laravel Tutorial : Push Notification With Firebase Cloud Messaging API (V1) & Laravel 10

Firebase is a service from Google to provide convenience and even make it easier for application developers to develop their applications. Firebase aka BaaS (Backend as a Service) is a solution offered by Google to speed up developer work. Google Declared Cloud Messaging API will be deprecated on 6/20/2024.

I was trying to migrate it to Firebase Cloud Messaging API (V1) as per suggestion of google.

Firebase cloud messaging helps to get real time notification to users Android/IOS Device.

Here I am using Firebase and Laravel as Back-end framework. From admin panel admin can publish notification

1. Install Laravel 10
First install Laravel and install Google Client package.

composer require google/apiclient

2. Configure Firebase

Login to your firebase account and create a project. Go to project settings and enable Firebase Cloud Messaging. Download the JSON File. Copy your JSON to public directory in your Laravel project.

3. Create Migration

Next we will create a migration to add the fcm_token to Users column.

			php artisan make:migration add_columns_to_users_table

			<?php

			use Illuminate\Database\Migrations\Migration;
			use Illuminate\Database\Schema\Blueprint;
			use Illuminate\Support\Facades\Schema;

			return new class extends Migration
			{
				/**
				 * Run the migrations.
				 */
				public function up(): void
				{
					Schema::table('users', function (Blueprint $table) {
						$table->string('fcm_token')->nullable();
					});
				}

				/**
				 * Reverse the migrations.
				 */
				public function down(): void
				{
					Schema::table('users', function (Blueprint $table) {
						//
					});
				}
			};
	
After updating the migration, run the migration command again.

		php artisan migrate
		
Add fcm_token to your User.php model file.

5. Create API Controller

In your controller file.

			public function sendPushNotification(Request $request)
			{
				$credentialsFilePath = public_path('firebase/firebase-service-account.json');
				$client = new \Google_Client();
				$client->setAuthConfig($credentialsFilePath);
				$client->addScope('https://www.googleapis.com/auth/firebase.messaging');

				$apiurl = 'https://fcm.googleapis.com/v1/projects/YOUR_PROJECT_ID/messages:send';
				$client->refreshTokenWithAssertion();
				$token = $client->getAccessToken();
				$access_token = $token['access_token'];

				$headers = [
					"Authorization: Bearer $access_token",
					'Content-Type: application/json'
				];

				$fcmToken = $request->input('fcm_token'); // Pass the FCM token dynamically
				if (empty($fcmToken)) {
					return response()->json(['error' => 'FCM token is missing or invalid'], 400);
				}

				$payload = [
					"message" => [
						"token" => $fcmToken,
						"notification" => [
							"title" => $request->input('title', 'Default Title'),
							"body" => $request->input('description', 'Default Description'),
						],
						"data" => [
							"customKey1" => "customValue1", // Optional custom data
							"customKey2" => "customValue2"
						]
					]
				];

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $apiurl);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

				$response = curl_exec($ch);

				if (curl_errno($ch)) {
					$error_msg = curl_error($ch);
					curl_close($ch);
					return response()->json(['error' => "Failed to send notification: $error_msg"], 500);
				}

				curl_close($ch);

				$responseData = json_decode($response, true);

				if (isset($responseData['name'])) {
					return response()->json(['message' => 'Notification sent successfully!']);
				} else {
					return response()->json([
						'error' => 'Failed to send notification',
						'response' => $responseData
					], 500);
				}
			}
			
6: Define the Route
Add the following route in your routes/web.php file:

		use App\Http\Controllers\NotificationController;

		Route::post('/send-push-notification', [NotificationController::class, 'sendPushNotification']
			
			
Steps to Download the Correct JSON File:
Go to your Firebase Project in the Firebase Console.
	1.Click on Settings (gear icon) → Project Settings.
	2.Navigate to the Service Accounts tab.
	3.Under Firebase Admin SDK, click Generate New Private Key.
	4.Download the JSON file. This file will have the required structure for server-side integration.
	Example Structure of the Correct JSON File:
The downloaded Service Account Key JSON will look like this:
	{
	  "type": "service_account",
	  "project_id": "your-project-id",
	  "private_key_id": "unique-private-key-id",
	  "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
	  "client_email": "firebase-adminsdk-abcde@your-project-id.iam.gserviceaccount.com",
	  "client_id": "12345678901234567890",
	  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
	  "token_uri": "https://oauth2.googleapis.com/token",
	  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
	  "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-abcde%40your-project-id.iam.gserviceaccount.com"
	}