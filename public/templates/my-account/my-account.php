<div class="ncs-my-account">
    <div class="tab">
        <ul class="ncs-nav-tabs">

            <?php foreach(ncs_account_tabs() as $tab_id => $tab):?>
            <li>
                <a class="tablinks <?php if(isset($tab['active'])){ echo 'active';} ?>"
                   href="<?php echo $tab['id'] ?>">
                    <?php esc_html_e($tab['title'], 'ncs-cart'); ?>
                </a>
            </li>
            <?php endforeach;?>

        </ul>
    </div>

    <?php foreach(ncs_account_tabs() as $tab_id => $tab):?>
        <div id="<?php echo $tab['id'] ?>" class="tabcontent <?php if(isset($tab['active'])){ echo 'active';} ?>">
            <?php if(!empty($tab['content'])):?>
                <?php ncs_template($tab['content'],'',  $tab); ?>
            <?php endif;?>
            <?php do_action("sc_tab_content_{$tab['id']}"); ?>
        </div>
    <?php endforeach;?>
</div>