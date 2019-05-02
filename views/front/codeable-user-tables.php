<table id="codeable_users_table" class="display">
    <thead>
    <tr>
        <th><?php echo __( 'Username', 'codeable-user-tables' ); ?></th>
        <th><?php echo __( 'Display Name', 'codeable-user-tables' ); ?></th>
        <th class="no-sort"><?php echo __( 'Role', 'codeable-user-tables' ); ?></th>
    </tr>
    </thead>
</table>
<script>
	var roles_data = <?php $roles = $this->get_all_roles(); echo json_encode( $roles->get_names() ); ?>;
</script>
