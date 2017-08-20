<?= $block('js') ?>
<script>
  // 图片上传后同步到微信中
  $('.js-image-upload').on('fileuploaded', function (event, data) {
    $.ajax({
      url: $.url('admin/wechat-medias/create'),
      type: 'post',
      data: {
        url: data.response.url
      }
    });
  });
</script>
<?= $block->end() ?>
