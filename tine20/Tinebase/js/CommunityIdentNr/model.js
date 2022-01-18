Tine.Tinebase.Model.CommunityIdentNrMixin = {
    statics: {
        satzArt2Text(satzArt) {
            switch (satzArt) {
                case 10: return 'Land';
                    break;
                case 20: return 'Regierungsbezirk';
                    break;
                case 30: return 'Region (nur in Baden-Württemberg)';
                    break;
                case 40: return 'Kreis';
                    break;
                case 50: return 'Gemeindeverband';
                    break;
                case 60: return 'Gemeinde';
                    break;
                default: return '';
            }
        }
    }
}
