{{--
  SweetAlert confirm for every ".hapus" button on a CMS list (the pattern of dashboard/series/index).
  The button carries the wording: data-confirm-title and data-confirm-text. Needs jQuery + Swal from the layout.
--}}
<script>
    $(document).ready(function () {
        $('.hapus').click(function (e) {
            e.preventDefault();
            var form = $(this).parents('form');
            Swal.fire({
                title: $(this).data('confirmTitle') || 'Hapus data?',
                text: $(this).data('confirmText') || '',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
