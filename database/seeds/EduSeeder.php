<?php

use Illuminate\Database\Seeder;

class EduSeeder extends Seeder
{
    const COMPANY_NAME = 'VennMobile';
    const PLAN_NAME = 'Unlimited data(no voice or sms)';
    const DEVICE_NAME = 'Samsung Tab A 10';
    const COUPON_NAME = 'Coupon on Samsung Galaxy A10 Unlimited Data';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $company = \App\Model\Company::select("*")
            ->where('name', self::COMPANY_NAME)
            ->first();
        if (!is_null($company)) {
            $coupon = \App\Model\Coupon::where('name', self::COUPON_NAME)->first();
            if(is_null($coupon)) {
                $coupon = \App\Model\Coupon::create([
                    'company_id' => $company->id,
                    'active' => 1,
                    'class' => 1,
                    'fixed_or_perc' => 1,
                    'amount' => 100,
                    'name' => self::COUPON_NAME,
                    'code' => '',
                    'num_cycles'=> 4,
                    'max_uses' => 4,
                    'num_uses' => 1,
                    'stackable' => 1,
                    'start_date' => \Illuminate\Support\Carbon::now(),
                    'end_date' => \Illuminate\Support\Carbon::now()->addMonths(4)
                ]);
            }

            $plan = \App\Model\Plan::where('name', self::PLAN_NAME)->first();
            if(is_null($plan)) {
                \App\Model\Plan::create([
                    'company_id' => $company->id,
                    'carrier_id' => 3,
                    'type' => 2,
                    'name' => 'Unlimited data',
                    'description' => '<p>
                                          <b style="font-size: 36px;">PERIOD.</b>
                                          </p>
                                        <p>
                                            <b>Unlimited data only plan (no voice or sms)</b>
                                        </p>
                                        <p><b><br></b></p>
                                        <p><b><span style="font-size: 18px;">Plan Includes:</span></b></p>
                                        <ul>
                                            <li class="desc">Samsung Tab A 10"</li>
                                            <li class="desc">Remote lockdown device to specific apps and controls with Samsung Knox</li>
                                            <li class="desc">3 months of Unlimited data included ($25/mo. Thereafter)</li>
                                            <li class="desc">Friendly 24/7 support</li>
                                            <li class="desc">10GB LTE Canada/Mexico roaming included</li>
                                        </ul>
                                        <br>
                                        <p><b>Call our dedicated support team and ask about the Education Package. Bulk pricing available 888.752.3646</b></p>
                                        ',
                    'notes' => 'test',
                    'amount_recurring' => 25,
                    'amount_onetime' => 0,
                    'regulatory_fee_type' => 1,
                    'regulatory_fee_amount' => 1,
                    'sim_required' => 0,
                    'taxable' => 1,
                    'show' => 1,
                    'sku' => 'D',
                    'signup_porting' => 1,
                    'subsequent_porting' => 1,
                    'area_code' => 2,
                    'imei_required' => 1,
                    'associate_with_device' => 1,
                    'affilate_credit' => 1,
                    'require_device_info' => 1,
                    'auto_add_coupon_id' => $coupon->id
                ]);
            }

            $device = \App\Model\Device::where('name', self::DEVICE_NAME)->first();
            if(is_null($device)) {
                $device = \App\Model\Device::create([
                    'company_id' => 3,
                    'carrier_id' => 3,
                    'type' => 2,
                    'sort' => 3,
                    'name' => 'Samsung Tab A 10',
                    'description' => '<p style="margin-bottom: 0px; padding-bottom: 6px; font-family: &quot;Human BBY Web&quot;, Arial, Helvetica, sans-serif; line-height: normal; color: inherit; font-size: 13px;">
                                            <b>Android™ 9.0</b>
                                      </p>
                                    <p style="margin-bottom: 0px; padding-bottom: 6px; font-family: &quot;Human BBY Web&quot;, Arial, Helvetica, sans-serif; line-height: normal; color: inherit; font-size: 13px;">
                                        <font color="#040c13" face="Human BBY Web, Arial, Helvetica, sans-serif"><span style="font-size: 13px;"><b><br></b></span></font>
                                    </p>
                                    <p style="margin-bottom: 0px; padding-bottom: 6px; font-family: &quot;Human BBY Web&quot;, Arial, Helvetica, sans-serif; line-height: normal; color: inherit; font-size: 13px;">
                                            <b>3G, Sprint 4G LTE</b>
                                      </p>
                                    <p style="margin-bottom: 0px; padding-bottom: 6px; font-family: &quot;Human BBY Web&quot;, Arial, Helvetica, sans-serif; line-height: normal; color: inherit; font-size: 13px;">
                                        <font color="#040c13" face="Human BBY Web, Arial, Helvetica, sans-serif"><span style="font-size: 13px;"><b><br></b></span></font>
                                    </p>
                                    <p style="margin-bottom: 0px; padding-bottom: 6px; font-family: &quot;Human BBY Web&quot;, Arial, Helvetica, sans-serif; line-height: normal; color: inherit; font-size: 13px;">
                                            <b>GPS Navigation</b>
                                      </p>
                                    <p style="margin-bottom: 0px; padding-bottom: 6px; font-family: &quot;Human BBY Web&quot;, Arial, Helvetica, sans-serif; line-height: normal; color: inherit; font-size: 13px;">
                                        <font color="#040c13" face="Human BBY Web, Arial, Helvetica, sans-serif"><span style="font-size: 13px;"><b><br></b></span></font>
                                    </p>
                                    <p style="margin-bottom: 0px; padding-bottom: 6px; font-family: &quot;Human BBY Web&quot;, Arial, Helvetica, sans-serif; line-height: normal; color: inherit; font-size: 13px;">
                                            <b>Messaging (Text & email)</b>
                                      </p>
                                    <p style="margin-bottom: 0px; padding-bottom: 6px; font-family: &quot;Human BBY Web&quot;, Arial, Helvetica, sans-serif; line-height: normal; color: inherit; font-size: 13px;">
                                        <font color="#040c13" face="Human BBY Web, Arial, Helvetica, sans-serif"><span style="font-size: 13px;"><b><br></b></span></font>
                                    </p>
                                    <p style="margin-bottom: 0px; padding-bottom: 6px; font-family: &quot;Human BBY Web&quot;, Arial, Helvetica, sans-serif; line-height: normal; color: inherit; font-size: 13px;">
                                            <b>Apps & social networking</b>
                                      </p>
                                    <p style="margin-bottom: 0px; padding-bottom: 6px; font-family: &quot;Human BBY Web&quot;, Arial, Helvetica, sans-serif; line-height: normal; color: inherit; font-size: 13px;">
                                        <font color="#040c13" face="Human BBY Web, Arial, Helvetica, sans-serif"><span style="font-size: 13px;"><b><br></b></span></font>
                                    </p>
                                    <p style="margin-bottom: 0px; padding-bottom: 6px; font-family: &quot;Human BBY Web&quot;, Arial, Helvetica, sans-serif; line-height: normal; color: inherit; font-size: 13px;">
                                            <b>Camera</b>
                                      </p>
                                    <p style="margin-bottom: 0px; padding-bottom: 6px; font-family: &quot;Human BBY Web&quot;, Arial, Helvetica, sans-serif; line-height: normal; color: inherit; font-size: 13px;">
                                        <font color="#040c13" face="Human BBY Web, Arial, Helvetica, sans-serif"><span style="font-size: 13px;"><b><br></b></span></font>
                                    </p>
                                    <p style="margin-bottom: 0px; padding-bottom: 6px; font-family: &quot;Human BBY Web&quot;, Arial, Helvetica, sans-serif; line-height: normal; color: inherit; font-size: 13px;">
                                            <b>Mobile Hotspot</b>
                                      </p>
                                    <p style="margin-bottom: 0px; padding-bottom: 6px; font-family: &quot;Human BBY Web&quot;, Arial, Helvetica, sans-serif; line-height: normal; color: inherit; font-size: 13px;">
                                        <font color="#040c13" face="Human BBY Web, Arial, Helvetica, sans-serif"><span style="font-size: 13px;"><b><br></b></span></font>
                                    </p>
                                    <p style="margin-bottom: 0px; padding-bottom: 6px; font-family: &quot;Human BBY Web&quot;, Arial, Helvetica, sans-serif; line-height: normal; color: inherit; font-size: 13px;">
                                            <b>Calendar</b>
                                      </p>
                                    <p style="margin-bottom: 0px; padding-bottom: 6px; font-family: &quot;Human BBY Web&quot;, Arial, Helvetica, sans-serif; line-height: normal; color: inherit; font-size: 13px;">
                                        <font color="#040c13" face="Human BBY Web, Arial, Helvetica, sans-serif"><span style="font-size: 13px;"><b><br></b></span></font>
                                    </p>',
                    'description_detail' => '<div class="row add-top-55 specifications">
                                                <div class="col-sm-4 col-xs-12">
                                                    <div class="img-wrap"></div>
                                                    <div class="img-wrap">
                                                        <div class="list-row"><h4 class="feature-title body-copy v-fw-medium"
                                                                                  style="margin-bottom: 0px; padding-bottom: 6px; line-height: normal;"><font
                                                            color="#040c13" face="Human BBY Web, Arial, Helvetica, sans-serif"><span style="font-size: 13px;">Bring the cinematic experience home with a widescreen Galaxy Tab A 10.1"" that delivers entertainment the whole family can enjoy. Feel the action come to life all around you with immersive Dolby Atmos surround sound. Browse, shop or binge-watch for hours with a long-lasting battery—plus, make room for all of your favorites with expandable storage*.</span>
                                                        </font></h4>
                                                            <div><br></div>
                                                        </div>
                                                        <div class="list-row"><h4 class="feature-title body-copy v-fw-medium"
                                                                                  style="margin-bottom: 0px; padding-top: 11px; padding-bottom: 6px; line-height: normal;">
                                                            <span style="font-size: 13px;"><b>Android 9.0 OS</b></span></h4><h4
                                                            class="feature-title body-copy v-fw-medium"
                                                            style="margin-bottom: 0px; padding-top: 11px; padding-bottom: 6px; line-height: normal;"><span
                                                            style="font-size: 13px;">Android Pie is the first time Google\'s heavily relying on gestures for navigating the UI.</span>
                                                        </h4><h4 class="feature-title body-copy v-fw-medium"
                                                                 style="margin-bottom: 0px; padding-top: 11px; padding-bottom: 6px; line-height: normal;"><span
                                                            style="font-size: 13px;"><b>Memory</b></span></h4><h4
                                                            class="feature-title body-copy v-fw-medium"
                                                            style="margin-bottom: 0px; padding-top: 11px; padding-bottom: 6px; line-height: normal;"><span
                                                            style="font-size: 13px;">Comes with only 32GB of storage built and supports upto 512GB Micro SD card.</span>
                                                        </h4><h4 class="feature-title body-copy v-fw-medium"
                                                                 style="margin-bottom: 0px; padding-top: 11px; padding-bottom: 6px; line-height: normal;"><span
                                                            style="font-size: 13px;"><b>Use your tab hotspot</b></span></h4><h4
                                                            class="feature-title body-copy v-fw-medium"
                                                            style="margin-bottom: 0px; padding-top: 11px; padding-bottom: 6px; line-height: normal;"><span
                                                            style="font-size: 13px;"> Share your tablet\'s internet connection with other devices via Wi-Fi.*</span>
                                                        </h4><h4 class="feature-title body-copy v-fw-medium"
                                                                 style="margin-bottom: 0px; padding-top: 11px; padding-bottom: 6px; line-height: normal;"><span
                                                            style="font-size: 13px;"><b>Access to Google Play</b></span></h4><h4
                                                            class="feature-title body-copy v-fw-medium"
                                                            style="margin-bottom: 0px; padding-top: 11px; padding-bottom: 6px; line-height: normal;"><span
                                                            style="font-size: 13px;">Browse and download apps, magazines, books, movies, and television programs directly to your phone.</span>
                                                        </h4><h4 class="feature-title body-copy v-fw-medium"
                                                                 style="margin-bottom: 0px; padding-top: 11px; padding-bottom: 6px; line-height: normal;"><span
                                                            style="font-size: 13px;"><b>Listen to your favorite tunes</b></span></h4><h4
                                                            class="feature-title body-copy v-fw-medium"
                                                            style="margin-bottom: 0px; padding-top: 11px; padding-bottom: 6px; line-height: normal;"><span
                                                            style="font-size: 13px;">Store and play your MP3 files on your phone.</span></h4><h4
                                                            class="feature-title body-copy v-fw-medium"
                                                            style="margin-bottom: 0px; padding-top: 11px; padding-bottom: 6px; line-height: normal;"><span
                                                            style="font-size: 13px;">* Depends on device memory and network availability. Additional carrier charges may apply.</span>
                                                        </h4></div>
                                                        <div class="list-row"><h4 class="feature-title body-copy v-fw-medium"
                                                                                  style="margin-bottom: 0px; padding-top: 11px; padding-bottom: 6px; font-family: &quot;Open Sans&quot;, sans-serif; line-height: normal; color: inherit; font-size: 13px;">
                                                            <span style="font-weight: bolder;">What\'s in the Box?</span></h4>
                                                            <h4 class="feature-title body-copy v-fw-medium"
                                                                style="margin-bottom: 0px; padding-top: 11px; padding-bottom: 6px; line-height: normal; color: inherit; font-size: 13px;">
                                                                <ul style="font-size: 12px;">
                                                                    <li style="padding-top: 11px; padding-bottom: 6px; color: inherit; line-height: normal; font-size: 13px;">
                                                                        Samsung Tab A 10
                                                                    </li>
                                                                    <li style="padding-top: 11px; padding-bottom: 6px; color: inherit; line-height: normal; font-size: 13px;">
                                                                        6150 mAh Li-Ion battery
                                                                    </li>
                                                                    <li style="padding-top: 11px; padding-bottom: 6px; color: inherit; line-height: normal; font-size: 13px;">
                                                                        Wall/USB Charger Type C
                                                                    </li>
                                                                    <li style="padding-top: 11px; padding-bottom: 6px; color: inherit; line-height: normal; font-size: 13px;">
                                                                        USB 2.0 Cable
                                                                    </li>
                                                                    <li style="padding-top: 11px; padding-bottom: 6px; color: inherit; line-height: normal; font-size: 13px;">
                                                                        Terms and Conditions
                                                                    </li>
                                                                    <li style="padding-top: 11px; padding-bottom: 6px; color: inherit; line-height: normal; font-size: 13px;">
                                                                        SIM card Installed
                                                                    </li>
                                                                </ul>
                                                            </h4>
                                                        </div>
                                                    </div>
                                                    <div class="add-top-10">
                                                        <ul class="in-box"></ul>
                                                    </div>
                                                </div>
                                                <div class="col-sm-8 col-xs-12 pad-right-3">
                                                    <div class="specs-wrap xs-add-top-2"><h4><b> Specifications </b></h4>
                                                        <div class="add-top-45 xs-add-top-3">
                                                            <div class="row item">
                                                                <div class="col-sm-8 col-xs-12 no-pad-left"><p class="specs"> Processor</p></div>
                                                                <div class="col-sm-4 col-xs-12 no-pad-right"><p class="specs"><span
                                                                    style="font-size: 12px;">Exynos 7904 Octa-Core 1.8GHz + 1.6GHz</span></p></div>
                                                            </div>
                                                            <div class="row item">
                                                                <div class="col-sm-8 col-xs-12 no-pad-left"><p class="specs"> Bluetooth profiles </p></div>
                                                                <div class="col-sm-4 col-xs-12 no-pad-right"><p class="specs"><span
                                                                    style="font-size: 12px;">A2DP,AVRCP,DI,HID,HOGP,HSP,OPP,PAN</span></p></div>
                                                            </div>
                                                            <div class="row item">
                                                                <div class="col-sm-8 col-xs-12 no-pad-left"><p class="specs"> Keyboard </p></div>
                                                                <div class="col-sm-4 col-xs-12 no-pad-right"><p class="specs"><span
                                                                    style="font-size: 12px;"> Capacitive</span></p></div>
                                                            </div>
                                                            <div class="row item">
                                                                <div class="col-sm-8 col-xs-12 no-pad-left"><p class="specs">Talk Time(s)</p>
                                                                   <p class="specs"> Up to 183 hours of music play time</p>
                                                                    <p class="specs"> Up to 14 hours and 30 minutes of video play time</p>
                                                                    <p class="specs"></p>
                                                                </div>
                                                            </div>
                                                            <div class="row item">
                                                                <div class="col-sm-8 col-xs-12 no-pad-left"><p class="specs"> Battery</p></div>
                                                                <div class="col-sm-4 col-xs-12 no-pad-right"><p class="specs"><span
                                                                    style="font-size: 12px;">6150 mAh Li-Ion battery</span></p></div>
                                                            </div>
                                                            <div class="row item">
                                                                <div class="col-sm-8 col-xs-12 no-pad-left"><p class="specs"> Display </p></div>
                                                                <div class="col-sm-4 col-xs-12 no-pad-right"><p class="specs"> 10.1" </p></div>
                                                            </div>
                                                            <div class="row item">
                                                                <div class="col-sm-8 col-xs-12 no-pad-left"><p class="specs"> Display Resolution </p></div>
                                                                <div class="col-sm-4 col-xs-12 no-pad-right"><p class="specs"> 1920 x 1200 pixels </p></div>
                                                            </div>
                                                            <div class="row item">
                                                                <div class="col-sm-8 col-xs-12 no-pad-left"><p class="specs"> Frequency </p></div>
                                                                <div class="col-sm-4 col-xs-12 no-pad-right"><p class="specs"><span style="font-size: 12px;">Supports the standard GSM (850, 900, 1800, 1900 MHz) and UMTS frequencies (850, 900, 1900, 2100 MHz).</span>
                                                                </p></div>
                                                            </div>
                                                            <div class="row item">
                                                                <div class="col-sm-8 col-xs-12 no-pad-left"><p class="specs"> GPS and Apps</p>
                                                                    <p class="specs"></p></div>
                                                                <div class="col-sm-4 col-xs-12 no-pad-right"><p class="specs"> GPS enabled</p></div>
                                                            </div>
                                                            <div class="row item">
                                                                <div class="col-sm-8 col-xs-12 no-pad-left"><p class="specs"> Weight</p>
                                                                    <p class="specs"></p></div>
                                                                <div class="col-sm-4 col-xs-12 no-pad-right"><p class="specs"> 16 oz</p></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>',
                    'tag_id' => null,
                    'notes' => null,
                    'primary_image' => '/imgs/tabs/samsung-a-10.1.png',
                    'amount' => 245,
                    'amount_w_plan' => 25,
                    'taxable' => 0,
                    'associate_with_plan' => 1,
                    'show' => 1,
                    'sku' => 'a67',
                    'os' => 'Android',
                    'shipping_fee' => 0
                ]);

                DB::table('device_image')->insert([
                    [
                        'device_id' => $device->id,
                        'source' => '/imgs/tabs/samsung-a-10.1.png',
                        'created_at' => \Illuminate\Support\Carbon::now(),
                        'updated_at' => \Illuminate\Support\Carbon::now(),
                    ], [
                        'device_id' => $device->id,
                        'source' => 'https://www.sprint.com/content/dam/sprint/commerce/devices/samsung/sg-tab-a10-1/deviceSKU_463x407_02.jpg',
                        'created_at' => \Illuminate\Support\Carbon::now(),
                        'updated_at' => \Illuminate\Support\Carbon::now(),
                    ], [
                        'device_id' => $device->id,
                        'source' => 'https://www.sprint.com/content/dam/sprint/commerce/devices/samsung/sg-tab-a10-1/deviceSKU_463x407_04.jpg',
                        'created_at' => \Illuminate\Support\Carbon::now(),
                        'updated_at' => \Illuminate\Support\Carbon::now(),
                    ]
                ]);
            }
        }
    }
}
