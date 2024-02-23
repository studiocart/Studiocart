<div class="studiocart">
    <div class="modal update-card-modal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-header">
                <h2><?php esc_html_e('Update Payment Method','ncs-cart'); ?></h2>
                <a href="#" class="btn-close closemodal" aria-hidden="true">&times;</a>
            </div>
            
            <div class="modal-body">
                <div class="success-msg" id="successMsg"></div>

                <section class="studiocart">
                    <div class="sc-section card-details">
                        <form id="updateCard">
                            <div class="row">
                        
                                <div class="form-group col-sm-12 ">
                                    <label for="first_name"><?php esc_html_e('Cardholder Name','ncs-cart'); ?><span class="req">*</span></label>
                                    <label id="cardholder-error" class="err-hide"></label>
                                    <input type="text" id="cardHolderName" name="card_holder" class="form-control required">
                                    <input type="hidden" id="sc-subscription-id" name="sc_subscription_id" value="<?php echo $_REQUEST['sc-plan']?>">
                                </div>

                                <div class="form-group col-sm-12 ">
                                    <label for="last_name"><?php esc_html_e('Card Number','ncs-cart'); ?><span class="req">*</span></label>
                                    <label id="card-error" class="err-hide"></label>
                                    <div id="card-number" class="form-control"></div>
                                </div>

                                <div class="form-group col-sm-6 ">
                                    <label><?php esc_html_e('Security Code','ncs-cart'); ?><span class="req">*</span></label>
                                    <label id="cvc-error" class="err-hide"></label>
                                    <div id="card-cvc" class="form-control"></div>
                                </div>

                                <div class="form-group col-sm-6">
                                    <label><?php esc_html_e('Expiry Date','ncs-cart'); ?><span class="req">*</span></label>
                                    <label id="expiry-error" class="err-hide"></label>
                                    <div id="card-expiry" class="form-control"></div>
                                </div>
                            
                                <!--<div class="form-group col-sm-12">
                                    <input type="checkbox" id="all-subscription" name="all_subscription" class="">
                                    <label for="sc-all-subscription">Set default for all active subscriptions</label>
                                </div>-->
                            
                                <div class="form-group col-sm-12">
                                    <button id="sc_update_card_button" type="submit" class="btn btn-primary btn-block">
                                        <span>
                                            <?php 
                                            if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'pay') {
                                                esc_html_e('Pay Now and Save Card','ncs-cart'); 
                                            } else {
                                                esc_html_e('Save Card','ncs-cart'); 
                                            }
                                            ?>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>

                <div class="sc_preloader" id="sc-preloader">
                    <svg width="48" height="48" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" stroke="#333333">
                        <g fill="none" fill-rule="evenodd">
                            <g transform="translate(1 1)" stroke-width="2">
                                <circle stroke-opacity=".5" cx="18" cy="18" r="18"/>
                                <path d="M36 18c0-9.94-8.06-18-18-18">
                                    <animateTransform attributeName="transform" type="rotate" from="0 18 18" to="360 18 18" dur="1s" repeatCount="indefinite"/>
                                </path>
                            </g>
                        </g>
                    </svg>
                </div>
            </div>
        </div>
    </div> 
</div>