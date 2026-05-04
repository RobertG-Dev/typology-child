<style>
    .sponsors {
        display: grid;
        padding: 50px;
        gap: 40px;
    }

    .sponsors a {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .sponsors a img {
        max-height: 100px;
    }

    @media screen and (max-width: 800px) {
        .sponsors {
            grid-template-columns: 1fr;
        }
    }

    @media screen and (min-width: 801px) {
        .sponsors {
            grid-template-columns: repeat(5, 1fr);
        }
    }
</style>

<h4 class='section-title' style="padding-top: 36px;">Organizacijos veiklą finansuoja</h4>

<div class='sponsors'>
    <a href='https://lnkc.lt/' target='_blank' rel='noopener noreferrer'><img src='https://lchs.lt/wp-content/uploads/LNKC-logotipas-juodas.png' alt='LNKC' style="max-height: 200px;"></a>
    <a href='https://www.ltkt.lt/' target='_blank' rel='noopener noreferrer'><img src='https://lchs.lt/wp-content/uploads/ltkt-LOGO.png' alt='LTKT'></a>
    <a href='https://vilnius.lt/lt/' target='_blank' rel='noopener noreferrer'><img src='https://lchs.lt/wp-content/uploads/VILNIUS_RED_TRANSPARENT_RGB.png' alt='Vilnius'></a>
    <a href='https://www.latga.lt/' target='_blank' rel='noopener noreferrer'><img src='https://lchs.lt/wp-content/uploads/Latga_logotipas_juodas_RGB.png' alt='LATGA'></a>
    <a href='https://www.lmma.eu/' target='_blank' rel='noopener noreferrer'><img src='https://lchs.lt/wp-content/uploads/lmma-logo.jpg' alt='LMMA'></a>
</div>