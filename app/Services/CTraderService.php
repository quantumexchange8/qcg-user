<?php

namespace App\Services;

use AleeDhillon\MetaFive\Entities\Trade;
use App\Models\User as UserModel;
use App\Models\AccountType as AccountTypeModel;
use App\Services\Data\CreateTradingAccount;
use App\Services\Data\CreateTradingUser;
use App\Services\Data\UpdateTradingAccount;
use App\Services\Data\UpdateTradingUser;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CTraderService
{
    private string $host = "https://live-quantumcapital.webapi.ctrader.com";
    private string $port = "8443";
    private string $login = "10012";
    private string $password = "Test1234.";
    private string $baseURL = "https://live-quantumcapital.webapi.ctrader.com:8443";
    private string $demoURL = "https://demo-quantumcapital.webapi.ctrader.com:8443";
    private string $token = "6f0d6f97-3042-4389-9655-9bc321f3fc1e";
    private string $brokerName = "quantumcapitalglobal";
    private string $environmentName = "live";

    public function connectionStatus(): array
    {
        return [
            'code' => 0,
            'message' => "OK",
        ];
    }

    public function CreateCTID($email)
    {
        $response = Http::acceptJson()->post($this->baseURL . "/cid/ctid/create?token=$this->token", [
            'brokerName' => $this->brokerName, //
            'email' => $email, //
            'preferredLanguage' => 'EN', //
        ])->json();
        Log::debug($response);

        return $response;
    }

    public function linkAccountTOCTID($meta_login, $password, $userId)
    {
        try {
            $response = Http::acceptJson()->post($this->baseURL . "/cid/ctid/link?token=$this->token", [
                'traderLogin' => $meta_login,
                'traderPasswordHash' => md5($password),
                'userId' => $userId,
                'brokerName' => $this->brokerName,
                'environmentName' => $this->environmentName,
                'returnAccountDetails' => false,
            ]);
            // Log::debug('linkAccountTOCTID response', ['response' => $response->json()]);
            return $response->json();
        } catch (Exception $e) {
            Log::error('Error in linkAccountTOCTID', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return null;
        }
    }

    public function linkDemoAccountTOCTID($meta_login, $password, $userId)
    {
        try {
            $response = Http::acceptJson()->post($this->demoURL . "/cid/ctid/link", [
                'traderLogin' => $meta_login,
                'traderPasswordHash' => md5($password),
                'userId' => $userId,
                'brokerName' => $this->brokerName,
                'environmentName' => $this->environmentName,
                'returnAccountDetails' => false,
            ]);
            // Log::debug('linkAccountTOCTID response', ['response' => $response->json()]);
            return $response->json();
        } catch (Exception $e) {
            Log::error('Error in linkAccountTOCTID', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return null;
        }
    }

    public function createUser(UserModel $user, $mainPassword, $investorPassword, $group, $leverage, AccountTypeModel $accountType, $leadCampaign = null, $leadSource = null, $remarks = null)
    {
        try {
            $accountResponse = Http::acceptJson()->post($this->baseURL . "/v2/webserv/traders?token=$this->token", [
                'hashedPassword' => md5($mainPassword),
                'groupName' => $group,
                'depositCurrency' => 'USD',
                'name' => $user->first_name,
                'description' => $remarks,
                'accessRights' => CTraderAccessRights::FULL_ACCESS,
                'balance' => 0,
                'leverageInCents' => $leverage * 100,
                'contactDetails' => [
                    'phone' => $user->phone,
                ],
                'accountType' => CTraderAccountType::HEDGED,
            ])->json();

            // Log::debug('createUser accountResponse', ['accountResponse' => $accountResponse]);

            if (isset($accountResponse['login'])) {
                $response = $this->linkAccountTOCTID($accountResponse['login'], $mainPassword, $user->ct_user_id);
                // Log::debug('linkAccountTOCTID result', ['response' => $response]);

                (new CreateTradingUser)->execute($user, $accountResponse, $accountType->id, $remarks);
                (new CreateTradingAccount)->execute($user, $accountResponse, $accountType);
                return $accountResponse;
            } else {
                Log::error('createUser error', ['accountResponse' => $accountResponse]);
                return null;
            }
        } catch (Exception $e) {
            Log::error('Error in createUser', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return null;
        }
    }

    public function createDemoUser(UserModel $user, $mainPassword, $investorPassword, $group, $leverage, $accountType, $leadCampaign = null, $leadSource = null, $remarks = null, $balance)
    {
        try {
            $accountResponse = Http::acceptJson()->post($this->demoURL . "/v2/webserv/traders", [
                'hashedPassword' => md5($mainPassword),
                'groupName' => $group,
                'depositCurrency' => 'USD',
                'name' => $user->first_name,
                'description' => $remarks,
                'accessRights' => CTraderAccessRights::FULL_ACCESS,
                'balance' => $balance * 100,
                'leverageInCents' => $leverage * 100,
                'contactDetails' => [
                    'phone' => $user->phone,
                ],
                'accountType' => CTraderAccountType::HEDGED,
            ])->json();

            // Log::debug('createUser accountResponse', ['accountResponse' => $accountResponse]);

            if (isset($accountResponse['login'])) {
                $response = $this->linkDemoAccountTOCTID($accountResponse['login'], $mainPassword, $user->ct_user_id);
                // Log::debug('linkAccountTOCTID result', ['response' => $response]);

                (new CreateTradingUser)->execute($user, $accountResponse, $accountType, $remarks);
                (new CreateTradingAccount)->execute($user, $accountResponse, $accountType);
                return $accountResponse;
            } else {
                Log::error('createUser error', ['accountResponse' => $accountResponse]);
                return null;
            }
        } catch (Exception $e) {
            Log::error('Error in createUser', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return null;
        }
    }

    public function getUser($meta_login)
    {
        $response = Http::acceptJson()->get($this->baseURL . "/v2/webserv/traders/$meta_login?token=$this->token")->json();
        //TraderTO
        Log::debug($response);
        return $response;
    }

    public function getGroups()
    {
        $response = Http::acceptJson()->get($this->baseURL . "/v2/webserv/tradergroups?token=$this->token")->json();
        //TraderTO
        Log::debug($response);
        return $response;
    }

    /**
     * @throws ConnectionException
     * @throws Exception
     * Change trader balance
     */
    public function createTrade($meta_login, $amount, $comment, $type): Trade
    {
        $fullUrl = $this->baseURL . "/v2/webserv/traders/$meta_login/changebalance?token=$this->token";

        $payload = [
            'login' => (int) $meta_login,
            'preciseAmount' => (double) $amount,
            'type' => $type,
            'comment' => $comment,
        ];

        $response = Http::acceptJson()
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($fullUrl, $payload);

        // Log the response details
        if ($type == 'DEPOSIT') {
            Log::info('DEPOSIT Response', [
                'status_code' => $response->status(),
                'response_body' => $response->json(),
            ]);
        }

        if (!$response->successful()) {
            Log::error('API Request Failed', [
                'status_code' => $response->status(),
                'error_body' => $response->json(),
            ]);
            throw new Exception('Failed to process the trade. Please check the logs for more details.');
        }

        // Create the Trade object
        $responseBody = $response->json();
        $trade = new Trade();
        $trade->setAmount($amount);
        $trade->setComment($comment);
        $trade->setType($type);
        $trade->setTicket($responseBody['balanceHistoryId'] ?? null);

        $this->getUserInfo($meta_login);
        return $trade;
    }

    public function getUserInfo($meta_login): void
    {
        $data = $this->getUser($meta_login);
        if ($data) {
            (new UpdateTradingUser)->execute($meta_login, $data);
            (new UpdateTradingAccount)->execute($meta_login, $data);
        }
    }

    public function updateLeverage($meta_login, $leverage)
    {
        try {
            $payload = [
                'leverageInCents' => $leverage * 100,
            ];

            $url = "{$this->baseURL}/v2/webserv/traders/$meta_login?token=$this->token";

            Log::info('Sending PATCH request', [
                'url' => $url,
                'payload' => $payload,
            ]);

            $response = Http::acceptJson()
                ->patch($url, $payload);

            if ($response->successful()) {
                Log::debug('Response received', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
            } elseif ($response->failed()) {
                Log::error('Request failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
            }
        } catch (Exception $e) {
            Log::error('Error in createUser', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }

        // dd($meta_login, $leverage);
        // Log::debug('0');
//        $tradingUser =  TradingUser::firstWhere('meta_login', $meta_login);
        // Log::debug($meta_login);
        // Log::debug('1');
        // Log::debug($leverage);
        // Log::debug('2');
        // Log::debug($tradingUser);

        // $response2 = Http::acceptJson()->put($this->baseURL . "/v2/webserv/traders/$meta_login?token=$this->token", [
        //     'login' => $meta_login,
        //     'groupName' => $tradingUser->meta_group,
        //     'leverageInCents' => $leverage * 100,
        // ])->json();

        // Log::debug('updateUser response2', ['updateResponse' => $response2]);
        // if ($response2->status() == 204) {
        //     $data = $this->getUser($meta_login);
        //     (new UpdateTradingUser)->execute($meta_login, $data);
        //     (new UpdateTradingAccount)->execute($meta_login, $data);
        // }
        // else {
        //     Log::error('updateUser error2', ['updateResponse' => $response2]);
        // }

//        $response = Http::acceptJson()->patch($this->baseURL . "/v2/webserv/traders/$meta_login?token=$this->token", [
//            'leverageInCents' => $leverage * 100,
//        ])->json();

        //     dd($response->status());

        // Log::debug('updateUser response1', ['updateResponse' => $response]);
        $data = $this->getUser($meta_login);
        (new UpdateTradingUser)->execute($meta_login, $data);
        (new UpdateTradingAccount)->execute($meta_login, $data);
        // if ($response->status() == 200) {
        // }
        // else {
        //     Log::error('updateUser error', ['updateResponse' => $response]);
        // }

    }

    public function deleteTrader($meta_login): void
    {
        Http::delete($this->baseURL . "/v2/webserv/traders/$meta_login?token=$this->token");
    }

    public function getMultipleTraders($from, $to, $groupId )
    {
        return Http::acceptJson()->get($this->baseURL . "/v2/webserv/traders/?from=$from&to=$to&groupId=$groupId&token=$this->token");
    }
}

class CTraderAccessRights
{
    const FULL_ACCESS = "FULL_ACCESS";
    const CLOSE_ONLY = "CLOSE_ONLY";
    const NO_TRADING = "NO_TRADING";
    const NO_LOGIN = "NO_LOGIN";
}

class CTraderAccountType
{
    const HEDGED = "HEDGED";
    const NETTED = "NETTED";
}

class ChangeTraderBalanceType
{
    const DEPOSIT = "DEPOSIT";
    const DEPOSIT_NONWITHDRAWABLE_BONUS = "DEPOSIT_NONWITHDRAWABLE_BONUS";
    const WITHDRAW = "WITHDRAW";
    const WITHDRAW_NONWITHDRAWABLE_BONUS = "WITHDRAW_NONWITHDRAWABLE_BONUS";
}
