<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
   
    public function sendOrderSMSNotification(Order $order)
    {
        try {
            // Format phone number (remove +265 if present and add it back)
            $phoneNumber = $order->phone;
            if (str_starts_with($phoneNumber, '+265')) {
                $phoneNumber = $phoneNumber;
            } elseif (str_starts_with($phoneNumber, '265')) {
                $phoneNumber = '+' . $phoneNumber;
            } elseif (str_starts_with($phoneNumber, '09')) {
                $phoneNumber = '+265' . substr($phoneNumber, 1);
            }

            $message = "CloudiMart: Your order {$order->order_id} has been placed successfully! Total: MWK " . number_format($order->total_amount) . ". We'll deliver to {$order->delivery_address}.";

            $basic = new \Vonage\Client\Credentials\Basic("6deb336f", "YlIUXfqvZ92rdRZR");
            $client = new \Vonage\Client($basic);

            $response = $client->sms()->send(
                new \Vonage\SMS\Message\SMS($phoneNumber, "CloudiMart", $message)
            );

            $message = $response->current();

            if ($message->getStatus() == 0) {
                Log::info('SMS sent successfully for order ' . $order->order_id);
                return true;
            } else {
                Log::error('SMS failed for order ' . $order->order_id . ' with status: ' . $message->getStatus());
                return false;
            }
        } catch (\Exception $e) {
            Log::error('SMS sending failed for order ' . $order->order_id . ': ' . $e->getMessage());
            return false;
        }
    }

    public function sendOrderEmailNotification(Order $order)
    {
        try {
            $user = $order->user;
            
            $orderData = [
                'order_id' => $order->order_id,
                'customer_name' => $user->fullName ?? $user->email,
                'total_amount' => number_format($order->total_amount),
                'delivery_address' => $order->delivery_address,
                'delivery_location' => $order->delivery_location,
                'phone' => $order->phone,
                'items' => $order->items,
                'created_at' => $order->created_at->format('M d, Y H:i A')
            ];

            Mail::send('emails.order_confirmation', $orderData, function($message) use ($user, $order) {
                $message->to($user->email)
                        ->subject('CloudiMart - Order Confirmation #' . $order->order_id)
                        ->from('noreply@cloudimart.com', 'CloudiMart');
            });

            Log::info('Email sent successfully for order ' . $order->order_id);
            return true;
        } catch (\Exception $e) {
            Log::error('Email sending failed for order ' . $order->order_id . ': ' . $e->getMessage());
            return false;
        }
    }
    public function sendOrderNotifications(Order $order)
    {
        $smsSent = $this->sendOrderSMSNotification($order);
        $emailSent = $this->sendOrderEmailNotification($order);

        return [
            'sms' => $smsSent,
            'email' => $emailSent
        ];
    }

   
    public function sendDeliveryConfirmationSMS(Order $order)
    {
        try {
            // Format phone number
            $phoneNumber = $order->phone;
            if (str_starts_with($phoneNumber, '+265')) {
                $phoneNumber = $phoneNumber;
            } elseif (str_starts_with($phoneNumber, '265')) {
                $phoneNumber = '+' . $phoneNumber;
            } elseif (str_starts_with($phoneNumber, '09')) {
                $phoneNumber = '+265' . substr($phoneNumber, 1);
            }

            $message = "CloudiMart: Your order {$order->order_id} has been successfully delivered! Thank you for shopping with us.";

            $basic = new \Vonage\Client\Credentials\Basic("6deb336f", "YlIUXfqvZ92rdRZR");
            $client = new \Vonage\Client($basic);

            $response = $client->sms()->send(
                new \Vonage\SMS\Message\SMS($phoneNumber, "CloudiMart", $message)
            );

            $message = $response->current();

            if ($message->getStatus() == 0) {
                Log::info('Delivery confirmation SMS sent successfully for order ' . $order->order_id);
                return true;
            } else {
                Log::error('Delivery confirmation SMS failed for order ' . $order->order_id . ' with status: ' . $message->getStatus());
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Delivery confirmation SMS sending failed for order ' . $order->order_id . ': ' . $e->getMessage());
            return false;
        }
    }

   
    public function sendDeliveryConfirmationEmail(Order $order)
    {
        try {
            $user = $order->user;
            
            $orderData = [
                'order_id' => $order->order_id,
                'customer_name' => $user->fullName ?? $user->email,
                'total_amount' => number_format($order->total_amount),
                'delivery_address' => $order->delivery_address,
                'delivered_at' => $order->delivered_at->format('M d, Y H:i A'),
                'items' => $order->items
            ];

            Mail::send('emails.delivery_confirmation', $orderData, function($message) use ($user, $order) {
                $message->to($user->email)
                        ->subject('CloudiMart - Order Delivered #' . $order->order_id)
                        ->from('noreply@cloudimart.com', 'CloudiMart');
            });

            Log::info('Delivery confirmation email sent successfully for order ' . $order->order_id);
            return true;
        } catch (\Exception $e) {
            Log::error('Delivery confirmation email sending failed for order ' . $order->order_id . ': ' . $e->getMessage());
            return false;
        }
    }

   
    public function sendDeliveryConfirmation(Order $order)
    {
        $smsSent = $this->sendDeliveryConfirmationSMS($order);
        $emailSent = $this->sendDeliveryConfirmationEmail($order);

        return [
            //'sms' => $smsSent,
            'email' => $emailSent
        ];
    }

//Test SMS endpoint
     
    public function sendSMSNotification(Request $request)
    {
        $basic = new \Vonage\Client\Credentials\Basic("6deb336f", "YlIUXfqvZ92rdRZR");
        $client = new \Vonage\Client($basic);

        $response = $client->sms()->send(
            new \Vonage\SMS\Message\SMS("265984946206", "CloudiMart", 'A text message sent using the Nexmo SMS API')
        );

        $message = $response->current();

        if ($message->getStatus() == 0) {
            return response()->json(['message' => 'The message was sent successfully.']);
        } else {
            return response()->json(['message' => 'The message failed with status: ' . $message->getStatus()]);
        }
    }
}
